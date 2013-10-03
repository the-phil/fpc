# PHP fpc
PHP **Form Process Class** is an attempt to simplify html form processing by taking a raw HTML form and automating the error handling.


### How it works
Take a simple HTML/XHTML/XML file with a *form* design and turn it into a dynamic form. 

Take a simple form design written in HTML/XHTML/XML

Take a simple HTML/XHTML/XML file and read it for *block* content based on the array name passed when a file is processed. When a *block* is found the *block* content is replaced with the string contained in the array defining the *block*. If multiple *blocks* are defined all *blocks* found will be replaced with the string contained in the array.


### About
The original idea was created to allow easier creating of dynamic HTML/XHTML/XML forms. In large team development projects designers would use WYSIWYG editors to create the HTML/XHTML/XML form. A need for faster processing of new designs while keeping the business logic of the form in tact.


### Block Definition
A *block* is a string variable with two square brackets surrounding it. The *block* content will look like `[errorMsg]`. 

