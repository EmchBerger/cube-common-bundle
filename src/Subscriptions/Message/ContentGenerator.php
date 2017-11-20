<?php
namespace CubeTools\CubeCommonBundle\Subscriptions\Message;

use CubeTools\CubeCommonBundle\Subscriptions\Reports\AbstractReport;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContentGenerator
{
    /**
     * @var string name of template responsible for creating email body
     */
    protected $bodyTemplate = 'CubeToolsCubeCommonBundle:Emails:subscriptionMessageContent.html.twig';

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    protected $templatingEngine;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var array data about reports
     */
    protected $reports;

    /**
     * @var \Swift_Message instance of message object
     */
    protected $messageObject;

    /**
     * @var string translated text for subject
     */
    protected $subjectText;

    /**
     * @var string translated text at the beginning of sent email
     */
    protected $introductionText = '';

    /**
     * @var string translated text at the end of sent email
     */
    protected $footerText = '';

    public function __construct(EngineInterface $templatingEngine, TranslatorInterface $translator)
    {
        $this->templatingEngine = $templatingEngine;
        $this->translator = $translator;
    }

    /**
     * Method setting reports.
     * @param array $reports
     */
    public function setReports($reports)
    {
        $this->reports = $reports;
    }

    /**
     * Setter for object responsible for creating email.
     * @param \Swift_Message $messageObject instance of message object
     */
    public function setMessageObject($messageObject)
    {
        $this->messageObject = $messageObject;
    }

    /**
     * Method setting attachments for message.
     */
    public function setAttachments()
    {
        foreach ($this->reports as $report) {
            $this->messageObject->attach(
                    \Swift_Attachment::fromPath(
                            $report[AbstractReport::KEY_REPORT_PATH], 
                            $report[AbstractReport::KEY_REPORT_FILE_CONTENT_TYPE]
                    )
            );
        }
    }

    /**
     * Method deleting attachments created for this email.
     */
    public function deleteAttachments()
    {
        foreach ($this->reports as $report) {
            unlink($report[AbstractReport::KEY_REPORT_PATH]);
        }
    }

    /**
     * Method setting body of message.
     */
    public function setBody()
    {
        $this->messageObject->setBody(
                $this->templatingEngine->render($this->bodyTemplate, 
                        array('reports' => $this->reports,
                            'introduction' => $this->introductionText,
                            'footer' => $this->footerText
                        )
                )
        );
    }

    /**
     * Method setting translated subject.
     * @param string $translatorKey key with translation for subject (if not present, then this text would be used as subject)
     * @param string $domain domain of translation
     */
    public function setSubjectTranslationKey($translatorKey, $domain = null)
    {
        $this->subjectText = $this->translator->trans($translatorKey, array(), $domain);
    }

    /**
     * Method setting translated introduction of email.
     * @param string $translatorKey key with translation for introduction (if not present, then this text would be used as introduction)
     * @param string $domain domain of translation
     */
    public function setIntroductionTranslationKey($translatorKey, $domain = null)
    {
        $this->introductionText = $this->translator->trans($translatorKey, array(), $domain);
    }

    /**
     * Method setting translated footer of email.
     * @param string $translatorKey key with translation for footer (if not present, then this text would be used as footer)
     * @param string $domain domain of translation
     */
    public function setFooterTranslationKey($translatorKey, $domain = null)
    {
        $this->footerText = $this->translator->trans($translatorKey, array(), $domain);
    }

    /**
     * Method setting subject of message (if text for it exists).
     */
    public function setSubject()
    {
        if (isset($this->subjectText)) {
            $this->messageObject->setSubject($this->subjectText);
        }
    }
}
