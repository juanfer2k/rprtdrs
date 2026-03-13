package com.repartidores.app

import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import androidx.core.content.ContextCompat
import com.getcapacitor.BridgeActivity

class MainActivity : BridgeActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        startTrackingService()
        
        val token = intent.getStringExtra("api_token")
        val id = intent.getIntExtra("repartidor_id", -1)

        if (token != null) {
            // Inyectar el token y el ID con un pequeño retardo para asegurar que el motor de Capacitor está listo
            Handler(Looper.getMainLooper()).postDelayed({
                bridge.webView.evaluateJavascript("localStorage.setItem('api_token', '$token')", null)
                if (id != -1) {
                    bridge.webView.evaluateJavascript("localStorage.setItem('repartidor_id', '$id')", null)
                }
            }, 1000)
        }
    }

    private fun startTrackingService() {
        val intent = Intent(this, TrackingService::class.java)
        ContextCompat.startForegroundService(this, intent)
    }
}
