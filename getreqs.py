from woocommerce import API

#setup
wcapi = API(
    url="http://example.com",
    consumer_key="ck_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    consumer_secret="cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    version="wc/v3"
)

# Retrieving all orders
response = wcapi.get("orders", params={"status": "on-hold"}).json()

# if response is empty, request was unsuccesful
if response:
    # print IDs of all orders on hold and update status
    for order in response:
        id = order['id']
        print(id)

        # data payload
        data = {
            "status": "completed"
        }

        # update status
        putResp = wcapi.put(f"orders/{id}", data)
        if putResp.status_code != 200:
            print("Put request failed")
else:
    print("Get request failed")


