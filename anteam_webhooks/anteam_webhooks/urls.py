from django.contrib import admin
from django.urls import path
from anteam_webhooks_app.views import webhook

urlpatterns = [
    path('admin/', admin.site.urls),
    path('webhook/', webhook, name='webhook'),
]