package com.example.appnhantbnganhang;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.os.Build;
import android.os.Bundle;
import android.provider.Settings;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends AppCompatActivity {

    private TextView tvStatus;
    private TextView tvLogs;
    private EditText etServerUrl;
    private Button btnGrantPermission;
    private Button btnSaveUrl;
    private Button btnTestSend;

    private SharedPreferences prefs;
    private final ExecutorService executorService = Executors.newSingleThreadExecutor();

    private final BroadcastReceiver logReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if (intent != null && intent.hasExtra("log")) {
                String message = intent.getStringExtra("log");
                appendLog(message);
            }
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        tvStatus = findViewById(R.id.tvStatus);
        tvLogs = findViewById(R.id.tvLogs);
        etServerUrl = findViewById(R.id.etServerUrl);
        btnGrantPermission = findViewById(R.id.btnGrantPermission);
        btnSaveUrl = findViewById(R.id.btnSaveUrl);
        btnTestSend = findViewById(R.id.btnTestSend);

        prefs = getSharedPreferences("PixelGearBankPrefs", MODE_PRIVATE);

        // Load saved server URL
        String savedUrl = prefs.getString("server_url", "http://192.168.1.10:8080/pixelgear/api/bank_webhook.php");
        etServerUrl.setText(savedUrl);

        // Save URL button
        btnSaveUrl.setOnClickListener(v -> {
            String newUrl = etServerUrl.getText().toString().trim();
            if (!newUrl.isEmpty()) {
                prefs.edit().putString("server_url", newUrl).apply();
                Toast.makeText(this, "Đã lưu URL Server thành công!", Toast.LENGTH_SHORT).show();
                appendLog("System: Đã cập nhật URL Server: " + newUrl);
            } else {
                Toast.makeText(this, "Vui lòng nhập URL hợp lệ", Toast.LENGTH_SHORT).show();
            }
        });

        // Grant Permission Button
        btnGrantPermission.setOnClickListener(v -> {
            Intent intent = new Intent(Settings.ACTION_NOTIFICATION_LISTENER_SETTINGS);
            startActivity(intent);
        });

        // Test Send Button
        btnTestSend.setOnClickListener(v -> {
            String testContent = "Vietcombank GD: +254,000VND so du 10,000,000VND. ND: Thanh toan DH 1 PixelGear";
            appendLog("TEST: Đang gửi dữ liệu mẫu test đơn hàng #1...");

            String targetUrl = etServerUrl.getText().toString().trim();
            executorService.execute(() -> {
                try {
                    URL url = new URL(targetUrl);
                    HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                    conn.setRequestMethod("POST");
                    conn.setRequestProperty("Content-Type", "application/json; utf-8");
                    conn.setRequestProperty("Accept", "application/json");
                    conn.setDoOutput(true);

                    String jsonInputString = "{\"content\": \"" + testContent + "\", \"package\": \"com.vietcombank.mobile\"}";

                    try (OutputStream os = conn.getOutputStream()) {
                        byte[] input = jsonInputString.getBytes(StandardCharsets.UTF_8);
                        os.write(input, 0, input.length);
                    }

                    int code = conn.getResponseCode();
                    runOnUiThread(() -> appendLog("TEST KẾT QUẢ: Server phản hồi Code " + code));
                    conn.disconnect();
                } catch (Exception e) {
                    runOnUiThread(() -> appendLog("TEST LỖI: " + e.getMessage()));
                }
            });
        });

        // Register BroadcastReceiver for log updates
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(logReceiver, new IntentFilter("com.example.appnhantbnganhang.LOG_UPDATE"), Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(logReceiver, new IntentFilter("com.example.appnhantbnganhang.LOG_UPDATE"));
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        checkNotificationPermission();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        try {
            unregisterReceiver(logReceiver);
        } catch (Exception ignored) {}
    }

    private void checkNotificationPermission() {
        String enabledListeners = Settings.Secure.getString(getContentResolver(), "enabled_notification_listeners");
        boolean isEnabled = enabledListeners != null && enabledListeners.contains(getPackageName());

        if (isEnabled) {
            tvStatus.setText("Trạng thái quyền: ✅ ĐÃ CẤP QUYỀN ĐỌC THÔNG BÁO");
            tvStatus.setTextColor(0xFF16A34A); // Green
            btnGrantPermission.setEnabled(false);
            btnGrantPermission.setText("QUYỀN ĐÃ ĐƯỢC KÍCH HOẠT");
        } else {
            tvStatus.setText("Trạng thái quyền: ❌ CHƯA CẤP QUYỀN ĐỌC THÔNG BÁO");
            tvStatus.setTextColor(0xFFDC2626); // Red
            btnGrantPermission.setEnabled(true);
            btnGrantPermission.setText("CẤP QUYỀN ĐỌC THÔNG BÁO NGÂN HÀNG");
        }
    }

    private void appendLog(String message) {
        String timeStamp = new SimpleDateFormat("HH:mm:ss", Locale.getDefault()).format(new Date());
        String formattedLog = "[" + timeStamp + "] " + message + "\n";
        tvLogs.append(formattedLog);
    }
}