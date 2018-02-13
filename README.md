# PrestaShop 1.7 Module for DIBS Easy Checkout #

This is PrestaShop 1.7 module for DIBS Easy Checkout, for more information see the following:
for integration documentation please go to: https://tech.dibspayment.com/nodeaddpage/prestashop17easy
If you have any issues please contact us [here](http://www.dibspayment.com/customer_support).

Installation

-You can install our Easy module for Prestashop by downloading the module here: https://github.com/DIBS-Payment-Services/Prestashop1.7_dibseasy/archive/master.zip

-Extract the ZIP

-Rename the folder to "dibseasy"

-ZIP the module back again

-Go to "Modules" - "Add a new module"

-choose your module ZIP file that you want to upload and click on the upload button

-Now you can configure your module with the merchant ID and keys found in your portal: https://portal.dibspayment.eu
 

Configuration

-Navigate to → Modules → Select your DIBS Easy Checkout module → Configure.

-Merchant ID - Add your merchant ID, found in your Easy portal.

-Live Secret key – Your live secret key received from DIBS.

-Live Checkout key – Your live checkout key received from DIBS.

-Test Secret key – your secret key for test purchases received from DIBS.

-Test Checkout key – Your checkout key for test purchases received from DIBS.

-Test mode – Tick the checkbox if you make purchases using the test credentials.
 
Required Prestashop settings
In order to have the correct email invoince template, you need to add relevant data.

To add EASY Checkout related information in order confirmation email, please use {dibs_html_block} in HTML template and {dibs_txt_block} in TXT template as placeholders.

### Order confirmation email configuration ###

To add DIBS EASY Checkout related information in order confirmation email, please use {dibs_html_block} in HTML template and {dibs_txt_block} in TXT template as placeholders.
