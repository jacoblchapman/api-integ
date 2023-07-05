from django.shortcuts import render
from django.http import HttpResponse
from django.views.decorators.csrf import csrf_exempt
import json

@csrf_exempt
def webhook(request):
    if request.method == 'POST':
        print('debug')
        payload = json.loads(request.body)
        print(payload)
    
        return HttpResponse(status=200)
    else:
        return HttpResponse(status=405)
    

# EXAMPLE POST REQUEST
# $uri = "http://127.0.0.1:8000/webhook/"
# $headers = @{}
# $body = @{
#     firstname = "Freddie"
#     lastname = "Moore"
# } | ConvertTo-Json

# Invoke-RestMethod -Uri $uri -Method POST -Headers $headers -Body $body
