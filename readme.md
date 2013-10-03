# PHP fpc
PHP **Form Process Class** is an attempt to simplify html form processing by taking a raw HTML form and automating the error handling.


### How it works
Take a simple HTML/XHTML/XML file with a *form* design and turn it into a dynamic form. 


### About
The original idea was created to allow easier creating of dynamic HTML/XHTML/XML forms. In large team development projects designers would use WYSIWYG editors to create the HTML/XHTML/XML form. A need for faster processing of new designs while keeping the business logic of the form in tact.


### Block Definition
A *block* is a string variable with two square brackets surrounding it. The *block* content will look like `[errorMsg]` or `[frmAction]`. 

