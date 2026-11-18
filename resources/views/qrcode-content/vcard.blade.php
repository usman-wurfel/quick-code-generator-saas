BEGIN:VCARD
VERSION:3.0
N:{{ $lastName }};{{ $firstName }}
FN:{{ $firstName }} {{ $lastName }}
TITLE:{{ $job }}
ORG:{{ $company }}
URL:{{ $website }}
EMAIL;WORK;INTERNET:{{ $email }}
TEL;WORK;VOICE:{{ $phone }}
TEL;CELL:{{ $mobile }}
TEL;FAX:{{ $fax }}
ADR:;;{{ $street }};{{ $city }};{{ $state }};{{ $zip }};{{ $country }}
END:VCARD