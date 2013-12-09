server 'syrup-a-02.keboola.com', :app, :web, :primary => true
server 'syrup-b-02.keboola.com', :app, :web, :primary => false
set :application, "syrup"
set :user, "deploy"
set :ssh_options, {:forward_agent => true}
set :deploy_to, "/www/syrup"