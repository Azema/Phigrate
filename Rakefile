require 'rubygems'
require 'cucumber'
require 'cucumber/rake/task'

Cucumber::Rake::Task.new(:ci) do |t|
    t.profile = "jenkins"
end

Cucumber::Rake::Task.new(:local) do |t|
    t.profile = "default"
end

task :default => :local
