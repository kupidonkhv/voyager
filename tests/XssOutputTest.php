<?php

namespace TCG\Voyager\Tests;

use Illuminate\Support\Facades\Auth;

class XssOutputTest extends TestCase
{
    public function testToastrMessagesAreJsonHexEscaped()
    {
        Auth::loginUsingId(1);

        $payload = '</script><img src=x onerror="document.documentElement.dataset.auditMarker=1">';
        session()->flash('message', $payload);
        session()->flash('alert-type', 'success');

        $response = $this->get(route('voyager.dashboard'))->response;
        $body = $response->getContent();

        $this->assertStringNotContainsString('</script><img', $body);
        $this->assertStringContainsString('\u003Cimg', $body);
    }
}
