<?php

namespace Coyote\Services\FormBuilder;

use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidatesWhenResolvedTrait;

abstract class FormRequest
{
    use ValidatesWhenResolvedTrait;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The redirector instance.
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * The URI to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirect;

    /**
     * The route to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectAction;

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'default';

    /**
     * The input keys that should not be flashed on redirect.
     *
     * @var array
     */
    protected $dontFlash = ['password', 'password_confirmation'];

    /**
     * @var bool
     */
    protected $enableValidation = true;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * Set up the validation rules
     */
    abstract protected function setupRules();

    /**
     * @return bool
     */
    abstract public function isSubmitted();

    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the Redirector instance.
     *
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @return Form
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return boolean
     */
    public function isValidationEnabled()
    {
        return $this->enableValidation;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setEnableValidation($flag)
    {
        $this->enableValidation = (bool) $flag;

        return $this;
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        $factory = $this->container->make(ValidationFactory::class);

        if ($this->isSubmitted() && $this->isValidationEnabled()) {
            $this->setupRules();
        }

        if (method_exists($this, 'validator')) {
            return $this->container->call([$this, 'validator'], compact('factory'));
        }

        return $this->makeValidatorInstance($factory);
    }

    /**
     * @param ValidationFactory $factory
     * @return Validator
     */
    protected function makeValidatorInstance(ValidationFactory $factory)
    {
        return $factory->make(
            $this->request->all(),
            $this->container->call([$this, 'rules']),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * @return array
     */
    public function rules()
    {
        return $this->rules;
    }

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Set custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        if ($this->request->ajax() || $this->request->wantsJson()) {
            return new JsonResponse($errors, 422);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->request->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * Get the response for a forbidden operation.
     *
     * @return \Illuminate\Http\Response
     */
    public function forbiddenResponse()
    {
        return new Response('Forbidden', 403);
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exception\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->response(
            $this->formatErrors($validator)
        ));
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->container->call([$this, 'authorize']);
        }

        return false;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exception\HttpResponseException
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException($this->forbiddenResponse());
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        return $validator->getMessageBag()->toArray();
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect) {
            return $url->to($this->redirect);
        } elseif ($this->redirectRoute) {
            return $url->route($this->redirectRoute);
        } elseif ($this->redirectAction) {
            return $url->action($this->redirectAction);
        }

        return $url->previous();
    }
}