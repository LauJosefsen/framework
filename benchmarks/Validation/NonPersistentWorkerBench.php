<?php

namespace Illuminate\Benchmarks\Validation;

use PhpBench\Attributes as Bench;

#[Bench\Iterations(30)]
#[Bench\Revs(1)]
#[Bench\AfterMethods(['verifyState'])]
class NonPersistentWorkerBench extends WorkerBenchmark
{
    #[Bench\BeforeMethods(['prepareParser'])]
    #[Bench\ParamProviders(['parserCases'])]
    public function benchParseRequest(): void
    {
        $this->parseRequest();
    }

    #[Bench\BeforeMethods(['prepareValidator'])]
    #[Bench\ParamProviders(['validatorCases'])]
    public function benchValidateRequest(): void
    {
        $this->validateRequest();
    }
}
