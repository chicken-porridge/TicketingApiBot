<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Models\Ticketing;
use App\Models\Instance;
use App\Models\Component;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Question\Question as QuestionQuestion;

class MainConversation extends Conversation
{
    protected $problem = '1';
    protected $instance = 1;
    protected $buttonArray = [];
    /**
     * First question
     */

    public function askInstances()
    {
        $all = Instance::all()->toArray();
        foreach ($all as $value) {
            $button = Button::create($value['name'])->value($value['id']);
            $buttonArray[] = $button;
        }

        $question = Question::create("Silahkan pilih instansi anda.")
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons($buttonArray);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $all = Instance::all()->toArray();
                foreach ($all as $value) {
                    switch ($answer->getValue()) {
                        case $value['id']:
                            $this->instance = $value['id'];
                            $this->jawabanNyaInstances($value['name']);
                            break;
                    }
                }
            }
        });
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

    public function jawabanNyaInstances($instansi)
    {
        $this->say('Instasi anda adalah <b>' . @ucwords($instansi).'</b>');
        $this->askProblems();
    }

    public function jawabanNyaProblem($kategori)
    {
        $this->ask(
            'Anda memilih kategori <b>' .@ucwords($kategori) . '.</b> Silahkan menjelaskan permasalahan yang dialami secara jelas dan ringkas.',
            function (Answer $answer) {
                if ($answer->getText() != '') {
                    $this->keluhan = $answer->getText();
                    $this->exit();
                }
                $jawaban = sprintf("Permasalahan anda <b>" . @ucwords($this->keluhan) . "</b> akan di proses oleh teknisi. \r\t\n\n Mohon ditunggu dalam waktu 3x24jam, Terimakasih 😊.");
                $this->say($jawaban);
            }
        );
    }

    public function exit()
    {
        $get_dataku = Ticketing::orderby('id', 'desc')->first();
        $full = explode("-", $get_dataku->no_ticketing);
        $lastfix = 'TKT' . '-' . ((int)$full[1] + 1);
        // $tkt = "TKT-0";
        // echo ++$tkt;
        $ticketing = new Ticketing;
        $ticketing->instance_id = $this->instance;
        $ticketing->technician_id = 0;
        $ticketing->date_created = Carbon::now();
        $ticketing->date_complete = null;
        $ticketing->no_ticketing = $lastfix; //bug
        $ticketing->component_id = $this->problem;
        $ticketing->problem = $this->keluhan;
        $ticketing->solving = null;
        $ticketing->status = "Open";
        $ticketing->save();
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