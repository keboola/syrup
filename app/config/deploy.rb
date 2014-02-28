set :stages,            %w(production staging local)
set :default_stage,     "staging"
set :stage_dir,         "app/config/deploy"
require 'capistrano/ext/multistage'

set :application,       "syrup"
# set :domain,            "ch-data.keboola.com"
set :deploy_to,         "/www/syrup"
set :app_path,          "app"
set :latest_path,       "latest"
set :ssh_options,       {:forward_agent => true}
set :user,              "deploy"
set :deploy_via,        :remote_cache

set :writable_dirs,     ["app/cache", "app/logs"]
set :webserver_user,    "apache"

set :shared_files,      ["composer.json"]
set :shared_children,   [app_path + "/logs", web_path + "/uploads"]
set :use_composer,      true
set :update_vendors,    true

set :scm,               :git
set :repository,        "git@github.com:keboola/syrup.git"

#role :web,              "syrup-a-01.keboola.com", "syrup-b-01.keboola.com"                         # Your HTTP server, Apache/etc
#role :app,              "syrup-a-01.keboola.com", "syrup-b-01.keboola.com"                         # This may be the same as your `Web` server
#role :db,               "syrup-a-01.keboola.com", "syrup-b-01.keboola.com", :primary => true       # This is where Symfony2 migrations will run

set  :use_sudo,         false
set  :keep_releases,    10

set :branch, "1.1.x"

#default_run_options[:pty] = true

before  'symfony:composer:update',  'symfony:copy_vendors'
before  'deploy:share_childs',      'symfony:copy_parameters'
after   'deploy:create_symlink',    'deploy:restart'

namespace :symfony do
	desc "Copy parameters.yml"
	task :copy_parameters, :except => { :no_release => true } do
		origin_file = shared_path + "/app/config/parameters.yml"
		destination_file = latest_release + "/app/config/parameters.yml"

		if File.exists?(destination_file)
			run "rm -f #{destination_file}"
		end

		run "cp #{origin_file} #{destination_file}"
		capifony_puts_ok
	end

	desc "Copy vendors from previous release"
	task :copy_vendors, :except => { :no_release => true } do
		capifony_pretty_print "--> Copying vendors from previous release"

		run "cp -a #{previous_release}/vendor #{latest_release}/"
		capifony_puts_ok
	end
end

namespace :deploy do
	# overwrite railsless-deploy task using old symlink task
	desc <<-DESC
	    Copies your project and updates the symlink. It does this in a \
	    transaction, so that if either `update_code' or `symlink' fail, all \
	    changes made to the remote servers will be rolled back, leaving your \
	    system in the same state it was in before `update' was invoked. Usually, \
	    you will want to call `deploy' instead of `update', but `update' can be \
	    handy if you want to deploy, but not immediately restart your application.
	DESC
	task :update do
		transaction do
			update_code
			# create_symlink - dont create current symlink
			create_symlink_latest
		end
	end

	# Create symlink to latest release for testing
	desc "Create symlink to latest release"
    task :create_symlink_latest do
        capifony_pretty_print "--> Symlink to latest release"

        run "rm -f #{deploy_to}/#{latest_path} && ln -s #{release_path} #{deploy_to}/#{latest_path}"
        capifony_puts_ok
    end

    # Apache needs to be restarted to make sure that the APC cache is cleared.
    # This overwrites the :restart task in the parent config which is empty.
    desc "Restart Apache"
    task :restart, :except => { :no_release => true }, :roles => :app do
        capifony_pretty_print "--> Restarting Apache"

        run "sudo /etc/init.d/httpd graceful"
        capifony_puts_ok
    end
end


# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

after "deploy", "deploy:cleanup"
