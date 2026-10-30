package com.openlot.android

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import com.openlot.android.ui.OpenLotAppRoot
import com.openlot.android.ui.theme.OpenLotTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            OpenLotTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    OpenLotAppRoot()
                }
            }
        }
    }
}
