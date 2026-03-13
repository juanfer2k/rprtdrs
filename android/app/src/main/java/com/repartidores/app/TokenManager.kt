package com.repartidores.app

import android.content.Context
import android.content.SharedPreferences

class TokenManager(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences("app_prefs", Context.MODE_PRIVATE)

    fun saveToken(token: String) {
        prefs.edit().putString("api_token", token).apply()
    }

    fun getToken(): String? {
        return prefs.getString("api_token", null)
    }

    fun clear() {
        prefs.edit().remove("api_token").apply()
    }
}
