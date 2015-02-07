<?php

namespace app\components;

class Tools
{

    /**
     * @param string $input
     * @return int
     */
    public static function dateSql2timestamp($input)
    {
        $parts = explode(" ", $input);
        $date  = array_map("IntVal", explode("-", $parts[0]));

        if (count($parts) == 2) {
            $time = array_map("IntVal", explode(":", $parts[1]));
        } else {
            $time = array(0, 0, 0);
        }

        return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
    }

    /**
     * @param string $input
     * @return string
     */
    public static function dateSql2de($input)
    {
        $parts = explode("-", $input);
        return $parts[2] . "." . $parts[1] . "." . $parts[0];
    }

    private static $last_time = 0;

    public static function debugTime($name)
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = sprintf("%14.0f", $sec * 10000 + $usec * 10000);
        if (static::$last_time) {
            echo "Zeit ($name): " . ($time - static::$last_time) . " (" . date("Y-m-d H:i:s") . ")<br>";
        }
        static::$last_time = $time;
    }

    /**
     * @param string $mailType
     * @param string $toEmail
     * @param string $subject
     * @param string $text
     * @param string $fromEmail
     * @param string $fromName
     */
    public static function sendEmailMandrill($mailType, $toEmail, $subject, $text, $fromEmail, $fromName)
    {
        /** @var \app\models\AntragsgruenAppParams $params */
        $params = \Yii::$app->params;

        $mandrill = new \Mandrill($params->mandrillApiKey);

        $tags = array(\app\models\db\EmailLog::getTypes()[$mailType]);

        $headers                   = array();
        $headers['Auto-Submitted'] = 'auto-generated';

        $message = array(
            'html'         => null,
            'text'         => $text,
            'subject'      => $subject,
            'from_email'   => $fromEmail,
            'from_name'    => $fromName,
            'to'           => array(
                array(
                    "name"  => null,
                    "email" => $toEmail,
                    "type"  => "to",
                )
            ),
            'important'    => false,
            'tags'         => $tags,
            'track_clicks' => false,
            'track_opens'  => false,
            'inline_css'   => true,
            'headers'      => $headers,
        );
        $mandrill->messages->send($message, false);
    }

    /**
     * @param int $mailType
     * @param string $toEmail
     * @param null|int $toPersonId
     * @param string $subject
     * @param string $text
     * @param null|string $fromName
     * @param null|string $fromEmail
     * @param null|array $noLogReplaces
     */
    public static function sendMailLog(
        $mailType,
        $toEmail,
        $toPersonId,
        $subject,
        $text,
        $fromName = null,
        $fromEmail = null,
        $noLogReplaces = null
    ) {
        /** @var \app\models\AntragsgruenAppParams $params */
        $params = \Yii::$app->params;

        $send_text = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $text
        ) : $text);

        $fromName     = ($fromName ? $fromName : $params->mailFromName);
        $fromEmail    = ($fromEmail ? $fromEmail : $params->mailFromEmail);
        $sendMailFrom = mb_encode_mimeheader($fromName) . ' <' . $fromEmail . '>';

        if ($params->mandrillApiKey) {
            static::sendEmailMandrill($mailType, $toEmail, $subject, $text, $fromEmail, $fromName);
        } else {
            mb_send_mail($toEmail, $subject, $send_text, "From: " . $sendMailFrom);
        }

        $obj = new \app\models\db\EmailLog();
        if ($toPersonId) {
            $obj->toUserId = $toPersonId;
        }
        $obj->toEmail   = $toEmail;
        $obj->type      = $mailType;
        $obj->fromEmail = $sendMailFrom;
        $obj->subject   = $subject;
        $obj->text      = $text;
        $obj->dateSent  = date("Y-m-d H:i:s");
        $obj->save();
    }
}
