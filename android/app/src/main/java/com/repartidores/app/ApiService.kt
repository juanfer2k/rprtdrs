package com.repartidores.app

import retrofit2.Call
import retrofit2.http.Body
import retrofit2.http.Header
import retrofit2.http.POST

interface ApiService {
    @POST("api/login.php")
    fun login(@Body credentials: LoginRequest): Call<LoginResponse>

    @POST("api/track.php")
    fun updateLocation(
        @Header("Authorization") token: String,
        @Body location: LocationUpdate
    ): Call<ApiResponse>
}

data class LoginRequest(
    val username: String,
    val password: String
)

data class LoginResponse(
    val status: String,
    val token: String?,
    val rol: String?,
    val id: Int?,
    val message: String?
)

data class LocationUpdate(
    val lat: Double,
    val lng: Double
)

data class ApiResponse(
    val status: String,
    val message: String?
)
