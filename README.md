# sib-sms

Laravel package for [sib-sms.com](https://www.sibsms.com/) (API V3) with optional database persistence.

### Install
In your Laravel project `composer.json`:

```ssh
composer require parviz-j/sib-sms
```

Set the variable configuration in the `.env` file.

```dotenv title=".env"
SIBSMS_API_KEY=YOU_API_KEY
SIBSMS_DEFAULT_SENDER=YOUR_SENDER_NUMBER
SIBSMS_PERSIST=true #need store in database
```