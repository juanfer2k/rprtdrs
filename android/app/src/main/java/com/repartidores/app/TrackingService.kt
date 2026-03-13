package com.repartidores.app

import android.app.*
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.IBinder
import android.os.Looper
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.android.gms.location.*
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

class TrackingService : Service() {

    private lateinit var fusedLocationClient: FusedLocationProviderClient
    private lateinit var locationCallback: LocationCallback
    private lateinit var apiService: ApiService
    private val TAG = "TrackingService"
    
    private val BASE_URL = "https://elcerritovalle.org/rprtdrs/" 
    private lateinit var tokenManager: TokenManager

    override fun onCreate() {
        super.onCreate()
        
        tokenManager = TokenManager(this)
        val token = tokenManager.getToken() ?: "token_repartidor_test"

        val retrofit = Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
        
        apiService = retrofit.create(ApiService::class.java)
        fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)

        locationCallback = object : LocationCallback() {
            override fun onLocationResult(locationResult: LocationResult) {
                locationResult.lastLocation?.let { location ->
                    sendLocationToServer(location.latitude, location.longitude, token)
                }
            }
        }

        createNotificationChannel()
        startForeground(1, createNotification("Iniciando seguimiento..."))
        requestLocationUpdates()
    }

    private fun requestLocationUpdates() {
        val locationRequest = LocationRequest.Builder(Priority.PRIORITY_HIGH_ACCURACY, 10000)
            .setMinUpdateIntervalMillis(5000)
            .build()

        try {
            fusedLocationClient.requestLocationUpdates(locationRequest, locationCallback, Looper.getMainLooper())
        } catch (unlikely: SecurityException) {
            Log.e(TAG, "Perdida de permiso de ubicación: $unlikely")
        }
    }

    private fun sendLocationToServer(lat: Double, lng: Double, token: String) {
        val update = LocationUpdate(lat, lng)
        apiService.updateLocation(token, update).enqueue(object : retrofit2.Callback<ApiResponse> {
            override fun onResponse(call: retrofit2.Call<ApiResponse>, response: retrofit2.Response<ApiResponse>) {
                if (response.isSuccessful) {
                    Log.d(TAG, "Ubicación enviada: $lat, $lng")
                    updateNotification("Repartidor en línea ($lat, $lng)")
                }
            }
            override fun onFailure(call: retrofit2.Call<ApiResponse>, t: Throwable) {
                Log.e(TAG, "Error enviando ubicación: ${t.message}")
            }
        })
    }

    private fun createNotification(content: String): Notification {
        return NotificationCompat.Builder(this, "tracking_channel")
            .setContentTitle("App de Reparto")
            .setContentText(content)
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .setOngoing(true)
            .build()
    }

    private fun updateNotification(content: String) {
        val notification = createNotification(content)
        val notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        notificationManager.notify(1, notification)
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val serviceChannel = NotificationChannel(
                "tracking_channel", "Tracking Service Channel",
                NotificationManager.IMPORTANCE_DEFAULT
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager.createNotificationChannel(serviceChannel)
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        return START_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        super.onDestroy()
        fusedLocationClient.removeLocationUpdates(locationCallback)
    }
}
