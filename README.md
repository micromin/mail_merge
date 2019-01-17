# Mail Merge for Gmail

A simple web application for sending customized email to multiple recipients. 
Here is the steps to run the mail merge locally:
  - Install PHP and git 
  - Install PHP composer
  - Clone the repo
  - Go to the repo directory and run ```composer install```
  - And finally, run the application: ```php bin/console server:run```
  - Yaay!

As you can see, you can fill in your email, name and password. If you have your 2 steps verification of, you need an app password to use the mail merge. In order to create an app password, go to your google account. On the app passwords page, create a new app password.

Finally, you can upload your email and other information file. The file should be in .csv format. If you have an excel file, save it as csv file and use it. IF you are using google sheets, then download the file as csv. Remember that the first row of your file should have the column names. `Note: one of the columns should be called email (lowercase).`

Finally, the twig template engine is used for templating. In order to customize the email for each recipient, use the column name (it is `case sensitive`) in {{ }}. For example, if one of you columns is name. Then you can say, hello {{name}} to customize it. 

![alt text](https://github.com/micromin/mail_merge/blob/master/screenshot.png?raw=true)
