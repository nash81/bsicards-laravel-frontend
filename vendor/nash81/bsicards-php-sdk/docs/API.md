# BSICARDS SDK - API Documentation

Base URL: `https://cards.bsigroup.tech/api/`

All endpoints require authentication headers:
- `publickey`: Your public API key
- `secretkey`: Your secret API key

## MasterCard API

### Create MasterCard

```php
$response = $client->mastercardCreateCard(
    string $userEmail,
    string $nameOnCard,
    string $pin
): array
```

**Parameters:**
- `$userEmail` (string): User's email address
- `$nameOnCard` (string): Name to display on card
- `$pin` (string): 4-digit PIN

**Response:**
```json
{
    "code": 200,
    "status": "success",
    "message": "Card Request Received"
}
```

---

### Get All MasterCards

```php
$response = $client->mastercardGetAllCards(string $userEmail): array
```

**Parameters:**
- `$userEmail` (string): User's email address

---

### Get Pending MasterCards

```php
$response = $client->mastercardGetPendingCards(string $userEmail): array
```

Returns MasterCards that are still being processed.

---

### Get Specific MasterCard

```php
$response = $client->mastercardGetCard(
    string $userEmail,
    string $cardId
): array
```

**Response includes:**
- Card number
- Expiry date
- CVV
- Available balance
- Billing address
- Card status

---

### Get Card Transactions

```php
$response = $client->mastercardGetTransactions(
    string $userEmail,
    string $cardId
): array
```

Returns transaction history with:
- Transaction amount
- Date and time
- Type (credit/debit)
- Status
- Merchant information

---

### Change Card PIN

```php
$response = $client->mastercardChangePin(
    string $userEmail,
    string $cardId,
    string $newPin
): array
```

**Parameters:**
- `$newPin` (string): New 4-digit PIN

---

### Freeze Card

```php
$response = $client->mastercardFreezeCard(
    string $userEmail,
    string $cardId
): array
```

Temporarily blocks the card. Can be unfrozen later.

---

### Unfreeze Card

```php
$response = $client->mastercardUnfreezeCard(
    string $userEmail,
    string $cardId
): array
```

Reactivates a frozen card.

---

### Fund Card

```php
$response = $client->mastercardFundCard(
    string $userEmail,
    string $cardId,
    string $amount
): array
```

**Parameters:**
- `$amount` (string): Amount to fund (minimum $10.00)

**Example:**
```php
$response = $client->mastercardFundCard(
    'user@example.com',
    'card-123',
    '50.00'
);
```

---

## Visa API

### Create Visa Card

```php
$response = $client->visaCreateCard(
    string $userEmail,
    string $nameOnCard,
    string $nationalIdNumber,
    string $nationalIdImage,
    string $userPhoto,
    string $dateOfBirth
): array
```

**Parameters:**
- `$userEmail` (string): User's email
- `$nameOnCard` (string): Name on card
- `$nationalIdNumber` (string): National ID number
- `$nationalIdImage` (string): URL to national ID image
- `$userPhoto` (string): URL to user photo
- `$dateOfBirth` (string): Date in YYYY-MM-DD format

**Example:**
```php
$response = $client->visaCreateCard(
    'user@example.com',
    'John Doe',
    '12345678',
    'https://example.com/id.pdf',
    'https://example.com/photo.jpg',
    '1990-01-15'
);
```

---

### Get All Visa Cards

```php
$response = $client->visaGetAllCards(string $userEmail): array
```

---

### Get Pending Visa Cards

```php
$response = $client->visaGetPendingCards(string $userEmail): array
```

---

### Get Specific Visa Card

```php
$response = $client->visaGetCard(
    string $userEmail,
    string $cardId
): array
```

---

---

## Digital Visa Wallet API

These endpoints manage virtual Visa cards backed by the Digital Visa Wallet infrastructure. Cards support 3DS auto-approve, Google Pay, and Apple Pay.

> **Note:** The card issuance fee and a minimum funding of **$5** are deducted from your Digital Visa issuing and Digital fund balances respectively upon card creation.

---

### Create Virtual Visa Wallet Card

```php
$response = $client->visaWalletCreateVirtualCard(
    string $userEmail,
    string $firstName,
    string $lastName
): array
```

**Parameters:**
- `$userEmail` (string): User's email address
- `$firstName` (string): Cardholder first name
- `$lastName` (string): Cardholder last name

**Response:**
```json
{
    "code": 201,
    "status": "success",
    "message": "Digital Wallet Visa card created successfully",
    "data": {
        "id": "cmmuuky2s003bc801b5888lzl",
        "cardName": "John Doe",
        "last4Digits": "8091",
        "currencyCode": "USD",
        "balance": "0.00",
        "paymentSystem": "VISA",
        "status": "ACTIVE",
        "expiresAt": "02/29",
        "createdAt": "2026-03-17T16:49:32.548Z"
    }
}
```

**Example:**
```php
$response = $client->visaWalletCreateVirtualCard(
    'user@example.com',
    'John',
    'Doe'
);
```

---

### Get All Digital Visa Wallet Cards

```php
$response = $client->visaWalletGetAllCards(string $userEmail): array
```

**Parameters:**
- `$userEmail` (string): User's email address

**Response:**
```json
{
    "code": 200,
    "status": "success",
    "message": "Cards fetched successfully",
    "data": [
        {
            "cardid": "cmmuubi0l0039c801j9my06s7",
            "nameoncard": "John Doe",
            "lastfour": "9523",
            "brand": "digitalvisa",
            "type": "virtual"
        }
    ]
}
```

---

### Get Specific Digital Visa Wallet Card

```php
$response = $client->visaWalletGetCard(
    string $userEmail,
    string $cardId
): array
```

**Parameters:**
- `$userEmail` (string): User's email address
- `$cardId` (string): Card ID

**Response includes:**
- Full card number
- Expiry month and year
- CVV
- Current balance
- Transaction history
- Card status

**Response:**
```json
{
    "code": 200,
    "status": "success",
    "message": "Card details fetched",
    "data": {
        "cardid": "cmmuubi0l0039c801j9my06s7",
        "nameoncard": "John Doe",
        "card_number": "4443635004479523",
        "type": "virtual",
        "brand": "digitalvisa",
        "status": "ACTIVE",
        "expiry_year": "29",
        "expiry_month": "02",
        "cvv": "433",
        "useremail": "user@example.com",
        "balance": "0.00",
        "isaddon": 0,
        "transactions": {
            "data": [],
            "total": 0,
            "page": 1,
            "per_page": 100,
            "totalPages": 0
        }
    }
}
```

---

### Fund Digital Visa Wallet Card

```php
$response = $client->visaWalletFundCard(
    string $userEmail,
    string $cardId,
    float|string $amount
): array
```

**Parameters:**
- `$userEmail` (string): User's email address
- `$cardId` (string): Card ID
- `$amount` (float|string): Amount to fund — **minimum $5.00**

**Example:**
```php
$response = $client->visaWalletFundCard(
    'user@example.com',
    'cmmuubi0l0039c801j9my06s7',
    50.00
);
```

---

### Get OTP for GPay / Apple Pay Provisioning

```php
$response = $client->visaWalletGetOTP(
    string $userEmail,
    string $cardId
): array
```

When the user adds their card manually to Google Pay or Apple Pay, they are asked to verify via Email OTP. Use this endpoint to retrieve that OTP and deliver it to your user (via email or on-screen display).

**Parameters:**
- `$userEmail` (string): User's email address
- `$cardId` (string): Card ID

---

### Block Digital Visa Wallet Card

```php
$response = $client->visaWalletBlockCard(
    string $userEmail,
    string $cardId
): array
```

Temporarily freezes the card. Can be unblocked later.

---

### Unblock Digital Visa Wallet Card

```php
$response = $client->visaWalletUnblockCard(
    string $userEmail,
    string $cardId
): array
```

Reactivates a previously blocked card.

---

## Administrator Operations

Admin methods retrieve account-wide data without requiring user email.

### Get Wallet Balance

```php
$response = $client->getWalletBalance(): array
```

---

### Get Deposits

```php
$response = $client->getDeposits(): array
```

---

### Get Transactions

```php
$response = $client->getTransactions(): array
```

---

### Get All Visa Cards

```php
$response = $client->getAllVisaCards(): array
```

---

### Get All MasterCards

```php
$response = $client->getAllMastercards(): array
```

---

### Get All Digital Cards

```php
$response = $client->getAllDigitalCards(): array
```

---

## Error Handling

```php
use BSICards\APIException;
    string $userEmail,
    string $cardId
): array
```

---

### Freeze Visa Card

```php
$response = $client->visaFreezeCard(
    string $userEmail,
    string $cardId
): array
```

---

### Unfreeze Visa Card

```php
$response = $client->visaUnfreezeCard(
    string $userEmail,
    string $cardId
): array
```

---

### Fund Visa Card

```php
$response = $client->visaFundCard(
    string $userEmail,
    string $cardId,
    string $amount
): array
```

---

## Digital Wallet API

### Create Virtual Card

```php
$response = $client->digitalCreateVirtualCard(
    string $userEmail,
    string $firstName,
    string $lastName,
    string $dateOfBirth,
    string $address1,
    string $postalCode,
    string $city,
    string $country,
    string $state,
    string $countryCode,
    string $phone
): array
```

**Parameters:**
- `$country` (string): 2-letter country code (e.g., 'GB', 'US')
- `$countryCode` (string): Phone country code (e.g., '44' for UK)
- `$dateOfBirth` (string): YYYY-MM-DD format

**Example:**
```php
$response = $client->digitalCreateVirtualCard(
    'user@example.com',
    'John',
    'Doe',
    '1990-01-15',
    '128 City Road',
    'EC1V 2NX',
    'London',
    'GB',
    'England',
    '44',
    '2071234567'
);
```

---

### Get All Digital Cards

```php
$response = $client->digitalGetAllCards(string $userEmail): array
```

---

### Get Specific Digital Card

```php
$response = $client->digitalGetCard(
    string $userEmail,
    string $cardId
): array
```

---

### Fund Digital Card

```php
$response = $client->digitalFundCard(
    string $userEmail,
    string $cardId,
    string $amount
): array
```

---

### Freeze Digital Card

```php
$response = $client->digitalFreezeCard(
    string $userEmail,
    string $cardId
): array
```

---

### Unfreeze Digital Card

```php
$response = $client->digitalUnfreezeCard(
    string $userEmail,
    string $cardId
): array
```

---

### Check 3DS Verification

```php
$response = $client->digitalCheck3DS(
    string $userEmail
): array
```

---

### Approve 3DS Transaction

```php
$response = $client->digitalApprove3DS(
    string $userEmail,
    string $cardId,
    string $eventId
): array
```

**Parameters:**
- `$eventId` (string): Event ID from 3DS authorization request

---

### Terminate Digital Card

```php
$response = $client->digitalTerminateCard(
    string $userEmail,
    string $cardId
): array
```

---

### Create Add-on Card

```php
$response = $client->digitalCreateAddonCard(
    string $userEmail,
    string $cardId
): array
```

**Note:** Add-on cards are charged $4.50 per card and share the same balance as the parent card.

---

### Get Loyalty Points

```php
$response = $client->digitalGetLoyaltyPoints(
    string $userEmail,
    string $cardId
): array
```

---

### Redeem Loyalty Points

```php
$response = $client->digitalRedeemPoints(
    string $userEmail,
    string $cardId
): array
```

---

## Error Handling

All methods throw `BSICards\APIException` on error:

```php
use BSICards\APIException;

try {
    $response = $client->mastercardCreateCard(...);
} catch (APIException $e) {
    echo "Error: " . $e->getMessage();
    echo "Code: " . $e->getCode();
}
```

## HTTP Status Codes

- `200` - Success
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (invalid credentials)
- `403` - Forbidden (access denied)
- `429` - Too Many Requests (rate limited)
- `500` - Server Error

---

## Rate Limiting

The API has rate limits. If exceeded, you receive a `429` response. Implement exponential backoff for retries.

---

## Data Types

- Amounts are strings (e.g., "50.00")
- Dates are YYYY-MM-DD format
- IDs are strings (UUIDs or hex)
- Emails are standard email format

