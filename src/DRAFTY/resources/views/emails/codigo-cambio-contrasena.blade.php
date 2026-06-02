<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código para cambiar tu contraseña en DRAFTY</title>
</head>
<body style="margin:0;padding:0;background:#050607;color:#f5f7f2;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#050607;padding:32px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#0f1214;border:1px solid rgba(190,255,72,0.24);border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="height:4px;background:#beff48;"></td>
                    </tr>
                    <tr>
                        <td style="padding:30px 26px 10px;">
                            <p style="margin:0 0 10px;color:#beff48;font-size:13px;font-weight:800;letter-spacing:0;text-transform:uppercase;">DRAFTY</p>
                            <h1 style="margin:0;color:#ffffff;font-size:28px;line-height:1.15;">Cambia tu contraseña</h1>
                            <p style="margin:16px 0 0;color:#cbd5d1;font-size:16px;line-height:1.6;">Introduce este código en tu perfil para establecer una nueva contraseña.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 26px;">
                            <div style="display:inline-block;padding:18px 24px;border-radius:10px;background:#07090a;border:1px solid rgba(190,255,72,0.35);color:#beff48;font-size:38px;font-weight:900;letter-spacing:8px;">
                                {{ $codigo }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 26px 30px;">
                            <p style="margin:0;padding:14px 16px;border-radius:8px;background:rgba(190,255,72,0.08);color:#dfff9f;font-size:14px;line-height:1.5;">
                                Este código caduca en 10 minutos. Si no has solicitado cambiar tu contraseña, puedes ignorar este correo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
