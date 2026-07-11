# Moodle Payment Gateway PayU

Welcome to the PayU plugin repository for Moodle.

### Installation
After you download the plugin.
1. First, you need to login as admin to your moodle site.
2. Then, go to **Site administration** -> **Plugins** -> **Install plugins**
3. You'll see the choose file button or you can drag and drop the plugin zip file to the box. Choose or drop the zip file plugin.
4. Then, click **install plugin from ZIP file**.
5. Then, click **continue** after installation complete.

## Steps you need to Integrate
1. Download and install the plugin.
2. If you haven't PayU account then you need to register.
3. Grab your PayU key and salt code.
4. Configure the Moodle payment account with PayU key and salt code.
5. Add 'Enrolment on payment' to the Moodle courses that you want

### Create PayU Account
> To create an account you may see it [here](https://onboarding.payu.in/app/account/signup).

### Configure Moodle payment account
1. For the configuration, go to **Site administration** -> **Plugins** -> **Manage payment gateway**.
2. You should found PayU on the list. Make sure it is enable.
3. Then go to **Site administration** -> **General** -> **Payments** -> **Payment accounts**
4. Create a payment account.
5. On the available payment account or the one that you've been created. Beside account name you'll see payment gateways column. Click on PayU.
6. Set **Enable** to be checked.
7. Input **key** as Merchant Key, **Salt** as Merchant Salt.
8. Then don't forget to set it in the right **environment**.

>***Please note, if you set wrong environment the access would be denied on payment.*

### Add Enrolment on payment
1. Go to course that you desired to add a payment.
2. On inside the course go to **participants**.
3. On the **participants** page, click the actions menu and select **Enrolment methods**.
4. Choose **Enrolment on payment** from the Add dropdown menu.
5. Select a payment account that you've been enabled PayU when on the configuration, amend the enrolment fee as necessary then click the button **Add method**.

## Support

If you encounter issues or bugs, please open an issue in the official GitHub repository:
[GitHub Issues](https://github.com/mukkaarunkumar/moodle-paygw_payu/issues)
