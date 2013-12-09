server 'kbc-devel-02.keboola.com', :app, :web, :primary => true
set :application, "syrup"
set :user, "deploy"
set :ssh_options, {:forward_agent => true}
set :deploy_to, "/www/testing/syrup"