Rem This scripts requires the class "VbsJson" which can be found here:
Rem http://demon.tw/my-work/vbs-json.html

Dim url
url = "<url>"

Rem OID of plugin "publicPages/000_objects"
Dim argstr
argstr = "plugin=1.3.6.1.4.1.37476.2.5.2.4.1.0" & _
	"&action=Insert" & _
	"&parent=oid:2.999" & _
	"&id=123" & _
	"&ra_email=test@example.com" & _
	"&comment=" & _
	"&asn1ids=aaa,bbb,ccc" & _
	"&iris=" & _
	"&confidential=0" & _
	"&weid=" & _
	"&batch_login_username=<username>" & _
	"&batch_login_password=<password>" & _
	"&batch_ajax_unlock_key=<unlock key>"

Set http = CreateObject("MSXML2.XMLHTTP.3.0")
http.Open "POST", url, False
http.setRequestHeader "Content-type", "application/x-www-form-urlencoded"
http.send argstr

Dim json
Set json = New VbsJson
Set o = json.Decode(http.responseText)

If http.status <> 200 Then
	MsgBox "HTTP status error: " & http.status
Else
	If o("error") <> "" Then
		MsgBox "OIDplus error: " & o("error")
	Else
		If o("status") < 0 Then
			MsgBox "OIDplus error status code " & o("status")
		Else
			MsgBox "OK! (Status " & o("status") & ")"
		End If
	End If
End If