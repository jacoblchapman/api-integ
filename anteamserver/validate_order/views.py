from django.shortcuts import render
from django.http import HttpResponse
from django.views.decorators.csrf import csrf_exempt
import json
from validate_order.utils import fetch_lat_lon, haversine
from rest_framework import status
from rest_framework.response import Response

weight_limit = 15 #weight limit in kg
distance_limit = 8 #distance limit in miles

@csrf_exempt
def validate_order(request):
    if request.method == 'POST':
        try:
            payload = json.loads(request.body)

            weight = int(payload['weight'])
            pickup_address = payload['pickup_address']
            dropoff_address = payload['dropoff_address']

            if weight < weight_limit:
                pickup_coord = fetch_lat_lon(pickup_address)
                dropoff_coord = fetch_lat_lon(dropoff_address)
                distance = haversine(pickup_coord[0], pickup_coord[1], dropoff_coord[0], dropoff_coord[1])

                if distance < distance_limit:
                    # order valid
                    response = HttpResponse('Order Valid')
                    response.status_code = 200
                    return response
        
                
                
        
            # order not valid
            response = HttpResponse('Order Not Valid')
            response.status_code = 400
            return response
        except Exception as e:
            print(e)
                
            


    else:
        response = HttpResponse('Invalid Request')
        response.status_code = 405
        return response
    

# $url = "http://127.0.0.1:8000/validate/"
# $params = @{
#     pickup_address = "23 Garden Road, Bromley, BR1 3LU"
#     dropoff_address = "5 Salthouse Lane, Nottingham, NG9 2GY"
#     weight = "12"
# }
# $jsonBody = $params | ConvertTo-Json
# $response = Invoke-RestMethod -Uri $url -Method Post -Body $jsonBody -ContentType "application/json"

# $status = $response.StatusCode
# $body = $response | ConvertTo-Json -Depth 4

# Write-Output "Status Code: $status"
# Write-Output "Response Body:"
# Write-Output $body
