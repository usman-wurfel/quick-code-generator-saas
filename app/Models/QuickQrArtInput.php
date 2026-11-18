<?php

namespace App\Models;


class QuickQrArtInput
{
    const WORKFLOW_V2 = 'generate_qr_art_v2';

    const WORKFLOW_V1 = 'generate_qr_art_v1.1';

    public string $workflow;
    public float $qrStrength;
    public int $qrSteps;
    public string $qrImage;
    public string $qrText;
    public string $negativePrompt;
    public int $seed;
    public string $webhook;

    public function __construct()
    {
    }

    public static function init(
        $workflow,
        $qrStrength,
        $qrImage,
        $qrText,
        $negativePrompt,
        $qrSteps,
        $webhook,
    ) {
        $instance = new static;

        $instance->workflow = $workflow;
        $instance->qrStrength = $qrStrength;
        $instance->qrImage = $qrImage;
        $instance->qrText = $qrText;
        $instance->negativePrompt = $negativePrompt;
        $instance->qrSteps = $qrSteps;
        $instance->webhook = $webhook;

        return $instance;
    }

    public static function fromArray($array)
    {
        $instance = new static;

        foreach ($array as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }

    public function toArray()
    {
        return (array)$this;
    }
}
