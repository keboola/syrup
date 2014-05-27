## 1.4.1
 * [Fix]        - debug handler stack trace
 * [Feature]    - updated to Symfony 2.4. 
 
## 1.4.0
 * [BC break]    - Session DB handler

## =====================================================================
 
## 1.3.0
 * [BC break]   - uses Syrup Component Bundle 1.5.0

## =====================================================================
 
## 1.2.7
 * [Fix]        - autoload.php and composer.json
 
## 1.2.6
 * [Feature]    - log fatal error trace to syslog

## 1.2.5
 * [Fix]        - added uploader config to parameters.yml.dist
 
## 1.2.4
 * [Fix]        - swiftmailer added to config.yml

## 1.2.3
 * [Fix]        - removed Slevomat DB dependency

## 1.2.2
 * [Refactor]   - Index action - list of components - is no longer using composer package

## 1.2.1
 * [Fix]        - composer.json cleanup

## 1.2.0
 * [Refactor]   - composer.json added to git
 * [Refactor]   - deploy keep only 10 latest releases
 * [Refactor]   - app/autoload.php path changed
 * [Refactor]   - app/console is no longer using Syrup\ComponentBundle\Console\Application using Listener to catch Exceptions from commands
 * [Fix]        - travis


## =====================================================================

## 1.1.19
 * [Refactor] - deploy fixed to branch 1.1.x

## 1.1.18
 * [Feature] - Swiftmailer bundle
 * [Fix] - added proper favicon
 * [Fix] - paramters.yml.dist

## 1.1.17
 * [Fix] - enabled form in config
 * [Feature] - copy vendors from previous release to speed up deploy

## 1.1.16
 * [Feature] - updated composer.json.template

## 1.1.15
 * [Feature] - Debug Exception Handler now logs into syslog

## 1.1.14
 * [Feature] - restart after deploy and also after symlink task

## 1.1.13
 * [Feature] - updated readme

## 1.1.12
 * [Refactoring] - new deploy mechanizm with "latest" symlink

## 1.1.11
 * [Refactoring] - Index Controller now shows components from installed.json file

## 1.1.10
 * [Fix] - deploy fixes

## 1.1.9
 * [Fix] - deploy fixes

## 1.1.8
 * [Fix] - permissions and deploy mechanizm enhancments

## 1.1.7
 * [Feature] - added logging to index controller command

## 1.1.6
 * [Refactoring] - Index page uses local composer.phar to show components

## 1.1.5
 * [Feature] - deploy to multiple servers
 * [Feature] - index page shows component versions

## 1.1.4
 * [Refactoring] - Custom routing loader moved to component-bundle

## 1.1.3
 * [Feature] - Added custom routing loader. Routing from components is loaded dynamicaly.

## 1.1.2
 * [Bug Fix] - Exception logging in console fix

## 1.1.1
 * [BC Break] - Upgraded to Symfony 2.3
 * [BC Break] - Upgraded to Storage API PHP Client 2.8.8

## 1.1.0
 * [Refactoring] - Syrup component bundles could be now loaded dynamicaly from parameters.yml instead of hardcoding them to AppKernel.php.



