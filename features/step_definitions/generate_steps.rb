When /^I run `generate.php ([\w_]*)$/ do |args|
    
end

Then /^it should pass whith:$/ do |string|
  string.should =~ /\nCreated migration: \d+_([A-Z]{1}\w+)+\.php\n/
end

Then /^it should fail whith:$/ do |string|
  string.should =~ /\n\s+This class name is already used. Please, choose another name.\n/
end
