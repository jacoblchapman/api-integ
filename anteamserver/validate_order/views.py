from django.shortcuts import render
from django.http import HttpResponse
from django.views.decorators.csrf import csrf_exempt
import json
from validate_order.utils import fetch_lat_lon, haversine
from rest_framework import status
from rest_framework.response import Response

@csrf_exempt
def validate_order(request):
    if request.method == 'POST':
        payload = json.loads(request.body)

        weight = int(payload['weight'])
        pickup_address = payload['pickup_address']
        dropoff_address = payload['dropoff_address']

        if weight < 15:
            pickup_coord = fetch_lat_lon(pickup_address)
            dropoff_coord = fetch_lat_lon(dropoff_address)
            distance = haversine(pickup_coord[0], pickup_coord[1], dropoff_coord[0], dropoff_coord[1])

            if distance < 8:
                # order valid
                response = HttpResponse('Order Valid')
                response.status_code = 200
                return response
                
                
        
        # order not valid
        response = HttpResponse('Order Not Valid')
        response.status_code = 400
        return response
                
            


    else:
        response = HttpResponse('Invalid Request')
        response.status_code = 405
        return response
    

# $url = "http://127.0.0.1:8000/validate/"
# $params = @{
#     pickup_address = "23 Garden Road, Bromley, BR1 3LU"
#     dropoff_address = "1 Great Ash, Lubbock Road, BR1 3LU"
#     weight = "12"
# }
# $jsonBody = $params | ConvertTo-Json
# $response = Invoke-RestMethod -Uri $url -Method Post -Body $jsonBody -ContentType "application/json"

# $status = $response.StatusCode
# $body = $response | ConvertTo-Json -Depth 4

# Write-Host "Status Code: $status"
# Write-Host "Response Body:"
# Write-Host $body
