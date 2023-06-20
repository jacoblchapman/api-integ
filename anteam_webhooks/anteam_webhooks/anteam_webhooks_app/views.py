from django.shortcuts import render
from django.http import HttpResponse
from django.views.decorators.csrf import csrf_exempt
import json

@csrf_exempt
def webhook(request):
    if request.method == 'POST':
        payload = json.loads(request.body)
        print(payload)
    
        return HttpResponse(status=200)
    else:
        return HttpResponse(status=405)
