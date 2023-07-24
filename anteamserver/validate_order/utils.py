from math import radians, cos, sin, asin, sqrt
import requests

api_key = "AIzaSyCyuj2O2NfgYm-wze8w1S8O6-6NYMgZXNo"
# taken off stack overflow
def haversine(lon1, lat1, lon2, lat2):
    """
    Calculate the great circle distance in kilometers between two points 
    on the earth (specified in decimal degrees)
    """
    # convert decimal degrees to radians 
    lon1, lat1, lon2, lat2 = map(radians, [lon1, lat1, lon2, lat2])

    # haversine formula 
    dlon = lon2 - lon1 
    dlat = lat2 - lat1 
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * asin(sqrt(a)) 
    # r = 6371 # Radius of earth in kilometers. Use 3956 for miles. Determines return value units.
    r = 3956
    return c * r

def fetch_lat_lon(address):
    # api_key = "AIzaSyCyuj2O2NfgYm-wze8w1S8O6-6NYMgZXNo"
    url = f"https://maps.googleapis.com/maps/api/geocode/json?address={address}&key={api_key}"

    response = requests.get(url)
    data = response.json()

    if data['status'] == 'OK':
        result = data['results'][0]
        location = result['geometry']['location']
        latitude = location['lat']
        longitude = location['lng']
        return (latitude, longitude)
    else:
        return None
