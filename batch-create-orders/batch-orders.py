from woocommerce import API
import time

# ck_9a95a4e8dbb568454cb520cb2cd3bc42d8b94847
# cs_403b2b6bafae84372cd86c8afcb2b492ec7987ca


wcapi = API(
    url="https://freddietestdev.online",
    consumer_key="ck_9a95a4e8dbb568454cb520cb2cd3bc42d8b94847",
    consumer_secret="cs_403b2b6bafae84372cd86c8afcb2b492ec7987ca",
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
        "postcode": "BR1 3LU",
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
        "postcode": "BR1 3LU",
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

for i in range(0,62):
    wcapi.post("orders", data).json()
    time.sleep(0.5)

print("Done")
