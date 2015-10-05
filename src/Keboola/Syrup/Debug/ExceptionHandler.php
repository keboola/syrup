<?php
/**
 * ExceptionHandler.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.8.13
 */

namespace Keboola\Syrup\Debug;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as BaseExceptionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

class ExceptionHandler extends BaseExceptionHandler
{
    private $debug;
    private $fileLinkFormat;
    protected $env;

    public function __construct($debug = true, $charset = 'UTF-8', $env = 'dev')
    {
        $this->env = $env;
        parent::__construct($debug);
        $this->debug = $debug;
        $this->fileLinkFormat = ini_get('xdebug.file_link_format') ?: get_cfg_var('xdebug.file_link_format');
    }

    /**
     * Registers the exception handler.
     * @param Boolean $debug
     * @param string  $env
     * @param null    $fileLinkFormat
     * @return ExceptionHandler The registered exception handler
     */
    public static function register($debug = true, $env = 'dev', $fileLinkFormat = null)
    {
        $handler = new static($debug, 'UTF-8', $env);

        set_exception_handler([$handler, 'handle']);

        return $handler;
    }

    /**
     * Creates the error Response associated with the given Exception.
     *
     * @param \Exception|FlattenException $exception An \Exception instance
     *
     * @return JsonResponse A JsonResponse instance
     */
    public function createResponse($exception)
    {
        if (!$exception instanceof FlattenException) {
            $exception = FlattenException::create($exception);
        }

        $yaml = new Parser();
        $parameters = $yaml->parse(file_get_contents(__DIR__.'/../../../../app/config/parameters.yml'));

        $appName = $parameters['parameters']['app_name'];
        $exceptionId = $appName . '-' . md5(microtime());

        $code = ($exception->getCode() >= 200 && $exception->getCode() < 600)?$exception->getCode():500;

        $priority = ($code < 500)?'ERROR':'CRITICAL';

        $logData = [
            'message' => $exception->getMessage(),
            'level' => $exception->getCode(),
            'channel' => 'app',
            'datetime' => ['date' => date('Y-m-d H:i:s')],
            'app' => $appName,
            'priority' => $priority,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'pid' => getmypid(),
            'trace' => $this->getTrace($exception),
            'exceptionId' => $exceptionId
        ];

        // log to syslog
        syslog(LOG_ERR, json_encode($logData));

        $response = [
            "status" => "error",
            'message' => 'An error occured. Please contact support@keboola.com',
            'exceptionId' => $exceptionId
        ];

        if (in_array($this->env, ['dev','test'])) {
            $response['message'] = $exception->getMessage();
        }

        // nicely format for console - @todo create ConsoleExceptionHandler
        if (php_sapi_name() == 'cli') {
            $resString = PHP_EOL;
            foreach ($response as $k => $v) {
                $resString .= $k . ': ' . $v . PHP_EOL;
            }
            $resString .= PHP_EOL;

            return new Response($resString, $code, $exception->getHeaders());
        }

        return new JsonResponse($response, $code, $exception->getHeaders());
    }

    public function getContent(FlattenException $exception)
    {
        switch ($exception->getStatusCode()) {
            case 404:
                $title = 'Sorry, the page you are looking for could not be found.';
                break;
            default:
                $title = 'Whoops, looks like something went wrong.';
        }

        $content = '';
        if ($this->debug) {
            try {
                $count = count($exception->getAllPrevious());
                $total = $count + 1;
                foreach ($exception->toArray() as $position => $e) {
                    $ind = $count - $position + 1;
                    $class = $this->formatClass($e['class']);
                    $message = nl2br($this->escapeHtml($e['message']));
                    $contentTemplate = <<<EOF
                        <h2 class="block_exception clear_fix">
                            <span class="exception_counter">%d/%d</span>
                            <span class="exception_title">%s (%d)%s:</span>
                            <span class="exception_message">%s</span>
                        </h2>
                        <div class="block">
EOF;
                    $content .= sprintf(
                        $contentTemplate,
                        $ind,
                        $total,
                        $class,
                        $e['code'],
                        $this->formatPath($e['trace'][0]['file'], $e['trace'][0]['line']),
                        $message
                    );
                    if (!empty($e['data'])) {
                        $content .= '<h2>Data</h2>';
                        $content .= '<pre>'.json_encode($e['data'], JSON_PRETTY_PRINT).'</pre>';
                    }
                    $content .= '<h2 style="margin-top:10px">Trace</h2>';
                    $content .= '<ol class="traces list_exception">';
                    foreach ($e['trace'] as $trace) {
                        $content .= '       <li>';
                        if ($trace['function']) {
                            $content .= sprintf('at %s%s%s(%s)', $this->formatClass($trace['class']), $trace['type'], $trace['function'], $this->formatArgs($trace['args']));
                        }
                        if (isset($trace['file']) && isset($trace['line'])) {
                            $content .= $this->formatPath($trace['file'], $trace['line']);
                        }
                        $content .= "</li>\n";
                    }

                    $content .= "    </ol>\n</div>\n";
                }
            } catch (\Exception $e) {
                // something nasty happened and we cannot throw an exception anymore
                if ($this->debug) {
                    $title = sprintf('Exception thrown when handling an exception (%s: %s)', get_class($e), $e->getMessage());
                } else {
                    $title = 'Whoops, looks like something went wrong.';
                }
            }
        }

        return <<<EOF
            <div id="sf-resetcontent" class="sf-reset">
                <h1>$title</h1>
                $content
            </div>
EOF;
    }

    private function formatClass($class)
    {
        $parts = explode('\\', $class);

        return sprintf("<abbr title=\"%s\">%s</abbr>", $class, array_pop($parts));
    }

    private function formatPath($path, $line)
    {
        $path = $this->escapeHtml($path);
        $file = preg_match('#[^/\\\\]*$#', $path, $file) ? $file[0] : $path;

        if ($linkFormat = $this->fileLinkFormat) {
            $link = str_replace(['%f', '%l'], [$path, $line], $linkFormat);

            return sprintf(' in <a href="%s" title="Go to source">%s line %d</a>', $link, $file, $line);
        }

        return sprintf(' in <a title="%s line %3$d" ondblclick="var f=this.innerHTML;this.innerHTML=this.title;this.title=f;">%s line %d</a>', $path, $file, $line);
    }

    private function getTrace(FlattenException $exception)
    {
        $all = $exception->toArray();

        $str = '';
        foreach ($all as $e) {
            $traces = $e['trace'];
            foreach ($traces as $trace) {
                $str .= "in file " . $trace['file'] . " on line " . $trace['line'] . PHP_EOL;
            }
        }
        return $str;
    }

    private function formatArgs(array $args)
    {
        $result = [];
        foreach ($args as $key => $item) {
            if ('object' === $item[0]) {
                $formattedValue = sprintf("<em>object</em>(%s)", $this->formatClass($item[1]));
            } elseif ('array' === $item[0]) {
                $formattedValue = sprintf("<em>array</em>(%s)", is_array($item[1]) ? $this->formatArgs($item[1]) : $item[1]);
            } elseif ('string' === $item[0]) {
                $formattedValue = sprintf("'%s'", $this->escapeHtml($item[1]));
            } elseif ('null' === $item[0]) {
                $formattedValue = '<em>null</em>';
            } elseif ('boolean' === $item[0]) {
                $formattedValue = '<em>'.strtolower(var_export($item[1], true)).'</em>';
            } elseif ('resource' === $item[0]) {
                $formattedValue = '<em>resource</em>';
            } else {
                $formattedValue = str_replace("\n", '', var_export($this->escapeHtml((string) $item[1]), true));
            }

            $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", $key, $formattedValue);
        }

        return implode(', ', $result);
    }

    /**
     * HTML-encodes a string.
     */
    private function escapeHtml($str)
    {
        return htmlspecialchars($str, ENT_QUOTES | (PHP_VERSION_ID >= 50400 ? ENT_SUBSTITUTE : 0), 'UTF-8');
    }

    public function getHtml(FlattenException $flattenException)
    {
        $css = $this->getStylesheet($flattenException);
        $content = $this->getContent($flattenException);
        return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta name="robots" content="noindex,nofollow" />
        <style>
            html{color:#000;background:#FFF;}body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td{margin:0;padding:0;}table{border-collapse:collapse;border-spacing:0;}fieldset,img{border:0;}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:normal;}li{list-style:none;}caption,th{text-align:left;}h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:normal;}q:before,q:after{content:'';}abbr,acronym{border:0;font-variant:normal;}sup{vertical-align:text-top;}sub{vertical-align:text-bottom;}input,textarea,select{font-family:inherit;font-size:inherit;font-weight:inherit;}input,textarea,select{*font-size:100%;}legend{color:#000;}

            html { background: #eee; padding: 10px }
            img { border: 0; }
            #sf-resetcontent { width:970px; margin:0 auto; }
            $css
        </style>
    </head>
    <body>
        $content
    </body>
</html>
EOF;
    }
}
