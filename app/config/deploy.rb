set :application,       "syrup"
# set :domain,            "ch-data.keboola.com"
set :deploy_to,         "/www/syrup"
set :app_path,          "app"
set :ssh_options,       {:forward_agent => true}
set :user,              "deploy"

set :shared_files,      ["app/config/parameters.yml","composer.json"]
set :shared_children,   [app_path + "/logs", web_path + "/uploads"]
set :use_composer,      true
set :update_vendors,    true

set :scm,               :git
set :repository,        "git@github.com:keboola/syrup.git"

role :web,              "syrup-a-01.keboola.com", "syrup-b-01.keboola.com"                         # Your HTTP server, Apache/etc
role :app,              "syrup-a-01.keboola.com", "syrup-b-01.keboola.com"                         # This may be the same as your `Web` server
role :db,               "syrup-a-01.keboola.com", "syrup-b-01.keboola.com", :primary => true       # This is where Symfony2 migrations will run

set  :use_sudo,         false
set  :keep_releases,    5

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

after "deploy",         "deploy:cleanup"
