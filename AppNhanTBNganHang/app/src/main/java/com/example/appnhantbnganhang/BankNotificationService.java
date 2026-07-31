package com.example.appnhantbnganhang;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.service.notification.NotificationListenerService;
import android.service.notification.StatusBarNotification;
import android.util.Log;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class BankNotificationService extends NotificationListenerService {

    private static final String TAG = "BankNotifService";
    private final ExecutorService executorService = Executors.newSingleThreadExecutor();

    @Override
    public void onNotificationPosted(StatusBarNotification sbn) {
        if (sbn == null) return;

        String packageName = sbn.getPackageName();
        Bundle extras = sbn.getNotification().extras;

        if (extras == null) return;

        String title = extras.getString("android.title", "");
        CharSequence textCharSeq = extras.getCharSequence("android.text");
        String text = textCharSeq != null ? textCharSeq.toString() : "";

        String fullContent = (title + " " + text).trim();

        Log.d(TAG, "Notification received from [" + packageName + "]: " + fullContent);

        // Filter Banking & MoMo App package names or keywords
        boolean isBankApp = packageName.contains("vietcombank") ||
                            packageName.contains("mbmobile") ||
                            packageName.contains("momo") ||
                            packageName.contains("techcombank") ||
                            packageName.contains("acb") ||
                            packageName.contains("bidv") ||
                            packageName.contains("agribank") ||
                            packageName.contains("vpbank") ||
                            fullContent.toLowerCase().contains("chuyen khoản") ||
                            fullContent.toLowerCase().contains("thanh toan") ||
                            fullContent.toLowerCase().contains("nhan tien") ||
                            fullContent.toLowerCase().contains("dh");

        if (isBankApp && !fullContent.isEmpty()) {
            sendNotificationToServer(fullContent, packageName);
        }
    }

    private void sendNotificationToServer(String content, String pkgName) {
        SharedPreferences prefs = getSharedPreferences("PixelGearBankPrefs", Context.MODE_PRIVATE);
        String targetUrl = prefs.getString("server_url", "http://192.168.1.10:8080/pixelgear/api/bank_webhook.php");

        // Broadcast to MainActivity for live log display
        broadcastLog("Đang gửi thông báo từ [" + pkgName + "]: " + content);

        executorService.execute(() -> {
            try {
                URL url = new URL(targetUrl);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json; utf-8");
                conn.setRequestProperty("Accept", "application/json");
                conn.setDoOutput(true);
                conn.setConnectTimeout(8000);
                conn.setReadTimeout(8000);

                String jsonInputString = "{\"content\": \"" + escapeJson(content) + "\", \"package\": \"" + escapeJson(pkgName) + "\"}";

                try (OutputStream os = conn.getOutputStream()) {
                    byte[] input = jsonInputString.getBytes(StandardCharsets.UTF_8);
                    os.write(input, 0, input.length);
                }

                int responseCode = conn.getResponseCode();
                Log.d(TAG, "Server Response Code: " + responseCode);

                if (responseCode == 200) {
                    broadcastLog("SUCCESS [200]: Đã bắn thông báo về Website!");
                } else {
                    broadcastLog("LỖI [" + responseCode + "]: Server phản hồi thất bại.");
                }

                conn.disconnect();

            } catch (Exception e) {
                Log.e(TAG, "Error sending notification to server", e);
                broadcastLog("LỖI KẾT NỐI: " + e.getMessage());
            }
        });
    }

    private void broadcastLog(String logMessage) {
        Intent intent = new Intent("com.example.appnhantbnganhang.LOG_UPDATE");
        intent.putExtra("log", logMessage);
        sendBroadcast(intent);
    }

    private String escapeJson(String input) {
        if (input == null) return "";
        return input.replace("\\", "\\\\")
                    .replace("\"", "\\\"")
                    .replace("\n", " ")
                    .replace("\r", " ");
    }
}
