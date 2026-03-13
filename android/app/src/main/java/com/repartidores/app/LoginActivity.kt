package com.repartidores.app

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

class LoginActivity : AppCompatActivity() {

    private lateinit var etUsername: EditText
    private lateinit var etPassword: EditText
    private lateinit var btnLogin: Button
    private lateinit var progressBar: ProgressBar
    private lateinit var tokenManager: TokenManager
    private lateinit var apiService: ApiService

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        tokenManager = TokenManager(this)
        
        // Si ya tiene token, ir directo al Mapa
        if (tokenManager.getToken() != null) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }

        setContentView(R.layout.activity_login)

        etUsername = findViewById(R.id.etUsername)
        etPassword = findViewById(R.id.etPassword)
        btnLogin = findViewById(R.id.btnLogin)
        progressBar = findViewById(R.id.progressBar)

        val retrofit = Retrofit.Builder()
            .baseUrl("http://host.docker.internal:8081/") // TODO: Cambiar por URL real
            .addConverterFactory(GsonConverterFactory.create())
            .build()
        apiService = retrofit.create(ApiService::class.java)

        btnLogin.setOnClickListener {
            val user = etUsername.text.toString()
            val pass = etPassword.text.toString()
            if (user.isNotEmpty() && pass.isNotEmpty()) {
                login(user, pass)
            }
        }
    }

    private fun login(user: String, pass: String) {
        progressBar.visibility = View.VISIBLE
        btnLogin.isEnabled = false

        apiService.login(LoginRequest(user, pass)).enqueue(object : Callback<LoginResponse> {
            override fun onResponse(call: Call<LoginResponse>, response: Response<LoginResponse>) {
                progressBar.visibility = View.GONE
                btnLogin.isEnabled = true
                
                if (response.isSuccessful && response.body()?.status == "success") {
                    val token = response.body()?.token
                    val id = response.body()?.id
                    if (token != null) {
                        tokenManager.saveToken(token)
                        val intent = Intent(this@LoginActivity, MainActivity::class.java)
                        intent.putExtra("api_token", token)
                        intent.putExtra("repartidor_id", id)
                        startActivity(intent)
                        finish()
                    }
                } else {
                    Toast.makeText(this@LoginActivity, "Credenciales incorrectas", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<LoginResponse>, t: Throwable) {
                progressBar.visibility = View.GONE
                btnLogin.isEnabled = true
                Toast.makeText(this@LoginActivity, "Error de red: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }
}
