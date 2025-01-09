@REM curl -X POST -H "Content-Type: application/json" -H "Accept: application/json" http://127.0.0.1:8000/api/login_check --data "{\"username\":\"sfr\",\"password\":\"testSFR\"}"
@REM curl -H "Content-Type: application/json" -H "Accept: application/json" http://127.0.0.1:8000/api/products
@body = @{
    username = "sfr"
    password = "testSFR"
};

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/login_check" -Method Post -Body $body
@REM $token = $response.access_token;

@REM $headers = @{
@REM     Authorization = "Bearer $token"
@REM }

@REM $response = Invoke-RestMethod "https://....../search-total" -Headers $headers
@REM return $response;