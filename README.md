# PAGOLIBRE 

## Description
Open source server that unifies multiple payment gateways:
- **Blockchain**
- **Gemini**
- **Coinbase**
- **Stripe**
- **Paypal**
- **2Checkout**

## Requirements
- Your web project must support HTML and PHP, and pages containing a checkout must be opened via a web browser.
- You cannot display the checkout on a HTML page opened directly on a local computer. Use a local server like Xampp instead.
- Your server must allow the access of the following file: pagolibre/ajax.php
- MySQL - The SQL mode "ONLY_FULL_GROUP_BY", and the setting "ANSI_QUOTES", must be disabled.
- PHP 7.4+
- Libraries:
  - CURL
  - ZIP ARCHIVE
  - GD extension
  - GMP
  - PHP modules: gmp, zip archive, cors. All required PHP modules should already be active by default.

## Installation
1. Clone the repository:
-  ```sh
   git clone https://github.com/PagoLibre/Server.git
2. Extract the folder
- extract the folder in a server location of your choice
3. Install
- Navigate to the link https://[your-site]/pagolibre/admin.php and complete the installation. Replace [your-site] with your website URL. If you changed the directory name, replace pagolibre with the new directory name.
4. Configure
- Once the installation is complete, log in with the username and password entered in the previous step and save the addresses of your cryptocurrency wallets in Settings > Cryptocurrency addresses. Mind that some cryptocurrency require an API key or a node to work
4. Ready to start
- You are done! You can start creating your first checkout.


## Documentation
- **Documentation**: More detailed documentation and other functions coming soon
- **Website**: Soon 
- **Complement**: Soon

