<?php

namespace Fm;

use SplFileObject;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Song
{
    const OPT_FILE    = 'file';
    const OPT_BITRATE = 'bitrate';

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        $this->options[self::OPT_FILE] = new SplFileObject($this->options[self::OPT_FILE]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([self::OPT_BITRATE, self::OPT_FILE]);
        $resolver->setAllowedTypes(self::OPT_FILE, 'string');
        $resolver->setAllowedTypes(self::OPT_BITRATE, 'int');
    }
}