# webhook approach
# would need a server running 24/7 to listen for evenst
# would need as many threads as businesses that want to offer anteam shipping
# also not necessary - don't need to grab order details as soon as it is placed, just need to grab order details when business owner wants to view order details

from woocommerce import API

#setup
wcapi = API(
    url="http://example.com",
    consumer_key="ck_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    consumer_secret="cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    version="wc/v3"
)

# create a webhook, can also be done on site
data = {
    "name": "Order created",
    "topic": "order.created",
    "delivery_url": "http://examplewebsite.com"
}

jsonResp = wcapi.post("webhooks", data).json()

id = jsonResp['id'] #webHook ID