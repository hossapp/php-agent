<?php

namespace Hoss;

class Agent
{
    public function __construct()
    {
        $this->config = new Configuration();
        $this->instrument();
    }

    private function instrument()
    {
        $self = $this;
        foreach ($this->config->getLibraryHooks() as $hookClass) {
            $hook = new $hookClass;
            $hook->enable(
                function (Request $request) use ($self) {
                    return $self->handleRequest($request);
                }
            );
        }
    }
}

?>
