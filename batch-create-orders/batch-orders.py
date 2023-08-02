from woocommerce import API
import time

# ck_532c86e72fab3243eadaf6e16a2619ca5e045ab7
# cs_49b01c2908ee5516c3eeb54cce1dfec0fb69d692


wcapi = API(
    url="https://freddietestdev.online",
    consumer_key="ck_532c86e72fab3243eadaf6e16a2619ca5e045ab7",
    consumer_secret="cs_49b01c2908ee5516c3eeb54cce1dfec0fb69d692",
    version="wc/v3"
)

data = {
    "payment_method": "bacs",
    "payment_method_title": "Direct Bank Transfer",
    "set_paid": True,
    "billing": {
        "first_name": "Freddie",
        "last_name": "Moore",
        "address_1": "6 Garden Road",
        "address_2": "",
        "city": "Bromley",
        "postcode": "NG11 4RU",
        "country": "UK",
        "email": "john.doe@example.com",
        "phone": "(555) 555-5555"
    },
    "shipping": {
        "first_name": "John",
        "last_name": "Doe",
        "address_1": "6 Garden Road",
        "address_2": "",
        "city": "Bromley",
        "postcode": "NG11 4RU",
        "country": "UK"
    },
    "line_items": [
        {
            "product_id": 18,
            "quantity": 2
        },
    ],
    "shipping_lines": [
        {
            "method_id": "flat_rate",
            "method_title": "Flat Rate",
            "total": "10.00"
        }
    ]
}

for i in range(0,20):
    print(wcapi.post("orders", data).json())
    time.sleep(0.1)

print("Done")
