# Installation & Running
* Put Mailchimp API key into .env file; Or set it as environment variable.
* Create a blank DB and setup DB privileges on MySql server; Put DB user and password into `.env` file  
###### Run following commands under project folder  
* To install dependencies, run:  
`composer install`  
To update  dependencies, run:  
`composer update`
* To generate DB tables, run:   
`php artisan doctrine:migrations:migrate`  
* To make phpunit test, run:  
`.\vendor\bin\phpunit --verbose test`   
(Replace backslash with forward slash on Linux and Mac)  
**ATTENTION:** *Mailchimp server actively blocks creating new members with same Email address; So everytime when you make functional test, please change value of `$memberData['email_address']` in class `MemberTestCase`*      
## Manual test
* Start local server:  
`php -S localhost:8000 -t public`  
**ATTENTION:** *Please don't append home page `public\index.php`, otherwise the following SwaggerUI URL won't work.*  
* Use Swagger UI: http://localhost:8000/swaggerui/index.html  
(replace `localhost:8000` with your server's URL)
* Use [Postman][1] to test the endpoints  
(see `app/Http/Routes/Api.php` for available endpoints)    
You can import test collection into Postman from file `var/docs/LoyaltyCorp.postman_collection.json)  
----------------------
# FRS

This project is based on [Laravel Lumen][2], to implement a new feature into an existing RESTful API.

The API is built to interact with [MailChimp via their API][3], handling CRUD operations for [LISTS][4] and [MEMBERS][5].

This task assumes all interaction will take place via this API, therefore data should be stored locally and 
only retrieved from MailChimp when required. 

You are required to add new features to the existing code:

- Add members to a list
- Update members within a list
- Remove members from a list
- Retrieve members from a list
#### Mandatory tools
- Each external libraries are loaded via [composer][9]
- The database layer used is [Doctrine][6] via the [laravel-doctrine/orm][7] package
- The interaction with [MailChimp API][3] is made using [pacely/mailchimp-apiv3][8]

[1]: https://www.getpostman.com/
[2]: https://lumen.laravel.com
[3]: http://developer.mailchimp.com/documentation/mailchimp/reference/overview
[4]: http://developer.mailchimp.com/documentation/mailchimp/reference/lists
[5]: http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members
[6]: http://www.doctrine-project.org/projects/orm.html
[7]: https://www.laraveldoctrine.org/docs/1.3/orm
[8]: https://github.com/pacely/mailchimp-api-v3
[9]: https://getcomposer.org/
