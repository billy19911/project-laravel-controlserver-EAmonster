// Sample minimal MT5 WebRequest integration for Laravel EA Cloud Controller
// Hardened architecture ready: /api/ea/get-config and /api/ea/report-status.

input string InpBaseUrl = "https://ea.mcstokomebel.com";
input string InpEaApiKey = "replace_with_env_ea_api_key";
input string InpAccountId = "12345678";
input bool   InpUseSignature = false; // Enable only if EA_REQUIRE_SIGNATURE=true on server.
input string InpEaTimestamp = "";     // Unix timestamp as string, example: 1715858400
input string InpEaSignature = "";     // HMAC-SHA256 hex generated externally

string TrimRightSlash(string url)
{
   string out = url;
   while(StringLen(out) > 0 && StringGetCharacter(out, StringLen(out) - 1) == '/')
      out = StringSubstr(out, 0, StringLen(out) - 1);
   return out;
}

string UrlEncodeAccountId(string accountId)
{
   string encoded = accountId;
   StringReplace(encoded, "%", "%25");
   StringReplace(encoded, " ", "%20");
   StringReplace(encoded, "#", "%23");
   StringReplace(encoded, "&", "%26");
   StringReplace(encoded, "?", "%3F");
   return encoded;
}

string BuildHeaders()
{
   string h = "Accept: application/json\r\n"
              "Content-Type: application/json\r\n"
              "X-EA-KEY: " + InpEaApiKey + "\r\n";

   if(InpUseSignature)
   {
      h += "X-EA-TIMESTAMP: " + InpEaTimestamp + "\r\n";
      h += "X-EA-SIGNATURE: " + InpEaSignature + "\r\n";
   }

   return h;
}

bool HttpGetConfig(string &response)
{
   string url = TrimRightSlash(InpBaseUrl) + "/api/ea/get-config?account_id=" + UrlEncodeAccountId(InpAccountId);
   char post[];
   char result[];
   string headers = BuildHeaders();
   string result_headers;

   int timeout = 5000;
   int code = WebRequest("GET", url, headers, timeout, post, result, result_headers);
   if(code == -1)
   {
      Print("WebRequest GET failed: ", GetLastError());
      return false;
   }

   response = CharArrayToString(result);
   Print("GET config HTTP ", code, " response: ", response);
   return (code >= 200 && code < 300);
}

bool HttpReportStatus(int currentLayers, double currentLot, double globalFloating, string guardStatus)
{
   string url = TrimRightSlash(InpBaseUrl) + "/api/ea/report-status";

   string body = StringFormat(
      "{\"account_id\":\"%s\",\"current_layers\":%d,\"current_accumulative_lot\":%.2f,\"global_floating\":%.2f,\"guard_status\":\"%s\"}",
      InpAccountId,
      currentLayers,
      currentLot,
      globalFloating,
      guardStatus
   );

   char data[];
   StringToCharArray(body, data, 0, StringLen(body));

   char result[];
   string headers = BuildHeaders();
   string result_headers;
   int timeout = 5000;

   int code = WebRequest("POST", url, headers, timeout, data, result, result_headers);
   if(code == -1)
   {
      Print("WebRequest POST failed: ", GetLastError());
      return false;
   }

   string response = CharArrayToString(result);
   Print("POST status HTTP ", code, " response: ", response);
   return (code >= 200 && code < 300);
}

void OnStart()
{
   Print("[EA SAMPLE] Starting WebRequest test for account: ", InpAccountId);

   string cfg = "";
   bool cfgOk = HttpGetConfig(cfg);
   if(!cfgOk)
      Print("[EA SAMPLE] get-config failed.");

   bool statusOk = HttpReportStatus(1, 0.01, -2.50, "LIVE");
   if(!statusOk)
      Print("[EA SAMPLE] report-status failed.");

   Print("[EA SAMPLE] Done. Check Experts tab logs.");
}
