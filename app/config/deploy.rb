set :application,       "syrup"
set :domain,            "ch-data.keboola.com"
set :deploy_to,         "/www/syrup"
set :app_path,          "app"
set :ssh_options,       {:forward_agent => true}

set :shared_files,      ["app/config/parameters.yml","composer.json"]
set :shared_children,   [app_path + "/logs", web_path + "/uploads"]
set :use_composer,      true
set :update_vendors,    true

set :scm,               :git
set :repository,        "git@github.com:keboola/syrup.git"

role :web,              domain                         # Your HTTP server, Apache/etc
role :app,              domain                         # This may be the same as your `Web` server
role :db,               domain, :primary => true       # This is where Symfony2 migrations will run

set  :use_sudo,         false
set  :keep_releases,    3

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

after "deploy",         "deploy:cleanup"
