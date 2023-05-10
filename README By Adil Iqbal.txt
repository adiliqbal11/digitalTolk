1.Authentication and validation missing 

Getting super_admin_role from env which is not a good approach
If someone clone the code from repo than there will be no env file to match id

And other than this if someone change the role id from repo, this will also a problem 


This code is using repository design pattern 

It is storing data without validating it same process with update







2.Test code for  "App/Helpers/TeHelper.php method willExpireAt"

public function testWillExpireAt()
{
    // Set the input values
    $due_time = '2023-05-20 10:00:00';
    $created_at = '2023-05-10 14:30:00';

    // Calculate the expected output
    $expected = '2023-05-12 14:30:00';

    // Call the function and get the actual output
    $actual = YourClassName::willExpireAt($due_time, $created_at);

    // Assert that the actual output matches the expected output
    $this->assertEquals($expected, $actual);
}








