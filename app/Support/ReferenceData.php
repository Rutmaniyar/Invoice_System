<?php

declare(strict_types=1);

namespace App\Support;

final class ReferenceData
{
    public static function timezones(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    public static function currencies(): array
    {
        return [
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => '$'],
            ['code' => 'BGN', 'name' => 'Bulgarian Lev', 'symbol' => 'BGN'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => '$'],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF'],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => 'CNY'],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$'],
            ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'CZK'],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'DKK'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => 'EUR'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => 'GBP'],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => 'GHS'],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => '$'],
            ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'HUF'],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
            ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'symbol' => 'ILS'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => 'INR'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => 'JPY'],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KES'],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => 'KRW'],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KWD'],
            ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'MAD'],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'MYR'],
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => 'NGN'],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'NOK'],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => '$'],
            ['code' => 'PEN', 'name' => 'Peruvian Sol', 'symbol' => 'PEN'],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => 'PHP'],
            ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'PLN'],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QAR'],
            ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'RON'],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR'],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'SEK'],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => '$'],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => 'THB'],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => 'TRY'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => 'VND'],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],
        ];
    }

    public static function currencyCodes(array $currencies): array
    {
        return array_map(static fn (array $currency): string => (string) $currency['code'], $currencies);
    }

    public static function countries(): array
    {
        return [
            'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Argentina', 'Armenia', 'Australia',
            'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium',
            'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil',
            'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon', 'Canada',
            'Cape Verde', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros',
            'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Democratic Republic of the Congo',
            'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador',
            'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 'Fiji', 'Finland', 'France',
            'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea',
            'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hong Kong', 'Hungary', 'Iceland', 'India',
            'Indonesia', 'Ireland', 'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya',
            'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Liechtenstein',
            'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta',
            'Mauritania', 'Mauritius', 'Mexico', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco',
            'Mozambique', 'Myanmar', 'Namibia', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua',
            'Niger', 'Nigeria', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Panama',
            'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar',
            'Romania', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines',
            'Samoa', 'San Marino', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone',
            'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'South Africa', 'South Korea', 'Spain',
            'Sri Lanka', 'Suriname', 'Sweden', 'Switzerland', 'Taiwan', 'Tanzania', 'Thailand', 'Togo',
            'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Uganda', 'Ukraine', 'United Arab Emirates',
            'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Vatican City',
            'Venezuela', 'Vietnam', 'Zambia', 'Zimbabwe',
        ];
    }
}
