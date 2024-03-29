<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Models\Ticketing;
use App\Models\Component;
use Illuminate\Support\Carbon;

class MainConversation extends Conversation
{
    protected $problem = '1';
    protected $buttonArray = [];
    protected $tkt = '';
    /**
     * First question
     */

    public function askInstances()
    {

        $question = Question::create("Silahkan masukkan nama instansi anda.")
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason');

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getText() != '') {
                $this->instance = $answer->getText();
            }
            $this->jawabanNyaInstances($answer);
        });
    }

    public function jawabanNyaInstances($instansi)
    {
        $this->say('Instasi anda adalah <b>' . @ucwords($instansi) . '</b>', ['parse_mode' => 'HTML']);
        $this->askAddress();
    }

    public function askAddress()
    {

        $question = Question::create("Silahkan masukkan alamat instansi anda.")
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason');

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getText() != '') {
                $this->address = $answer->getText();
            }
            $this->jawabanNyaAddress($answer);
        });
    }

    public function jawabanNyaAddress($address)
    {
        $this->say('Alamat instasi anda adalah <b>' . @ucwords($address) . '</b>', ['parse_mode' => 'HTML']);
        $this->askProblems();
    }

    public function askProblems()
    {
        $all = Component::all()->toArray();
        foreach ($all as $value) {
            $button = Button::create(@ucwords($value['name']))->value($value['id']);
            $buttonArray[] = $button;
        }

        $question = Question::create("Silahkan pilih kategori permasalahan / aduan.")
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons($buttonArray);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $all = Component::all()->toArray();
                foreach ($all as $value) {
                    switch ($answer->getValue()) {
                        case $value['id']:
                            $this->problem = $value['id'];
                            $this->jawabanNyaProblem($value['name']);
                            break;
                    }
                }
            }
        });
    }

    public function jawabanNyaProblem($kategori)
    {
        $this->say('Anda memilih kategori <b>' . @ucwords($kategori) . '.</b>', ['parse_mode' => 'HTML']);
        $this->ask(
            'Silahkan menjelaskan permasalahan yang dialami secara jelas dan ringkas.',
            function (Answer $answer) {
                if ($answer->getText() != '') {
                    $this->keluhan = $answer->getText();
                    $this->exit();
                }
                $jawaban = sprintf('No.Tiket <b>#' . $this->tkt . '</b>, Permasalahan anda <b>"' . @$this->keluhan . '"</b>. Anda akan di hubungi oleh teknisi kami untuk penanganan lebih lanjut. Terimakasih 😊.');
                $this->say($jawaban, ['parse_mode' => 'HTML']);
            }
        );
    }

    public function exit()
    {
        $get_dataku = Ticketing::orderby('id', 'desc')->first();
        $full = explode("-", $get_dataku->no_ticketing);
        $lastfix = 'TKT' . '-' . ((int)$full[1] + 1);
        $ticketing = new Ticketing;
        $ticketing->instance_name = $this->instance;
        $ticketing->address = $this->address;
        $ticketing->date_created = Carbon::now();
        $ticketing->date_complete = null;
        $ticketing->no_ticketing = $lastfix;
        $ticketing->component_id = $this->problem;
        $ticketing->problem = $this->keluhan;
        $ticketing->status = "Open";
        $ticketing->save();
        $this->tkt = $lastfix;
        return true;
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askInstances();
    }
}
