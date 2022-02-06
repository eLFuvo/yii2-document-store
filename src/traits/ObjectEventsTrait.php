<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-06-04
 * Time: 11:14
 */

namespace elfuvo\yiiDocumentStore\traits;

use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\UnknownMethodException;

/**
 * Trait ObjectEventsTrait
 * @package elfuvo\yiiDocumentStore\traits
 */
trait ObjectEventsTrait
{
    /**
     * @var array the attached event handlers (event name => handlers)
     */
    private array $_events = [];

    /**
     * @var Behavior[]|null the attached behaviors (behavior name => behavior). This is `null` when not initialized.
     */
    private ?array $_behaviors = null;

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [];
    }

    /**
     * Makes sure that the behaviors declared in [[behaviors()]] are attached to this component.
     * @throws \yii\base\InvalidConfigException
     */
    protected function ensureBehaviors()
    {
        if ($this->_behaviors === null) {
            $this->_behaviors = [];
            foreach ($this->behaviors() as $name => $behavior) {
                $this->attachBehaviorInternal($name, $behavior);
            }
        }
    }

    /**
     * Attaches a behavior to this component.
     * @param string|int $name the name of the behavior. If this is an integer, it means the behavior
     * is an anonymous one. Otherwise, the behavior is a named one and any existing behavior with the same name
     * will be detached first.
     * @param string|array|Behavior $behavior the behavior to be attached
     * @return Behavior the attached behavior.
     * @throws \yii\base\InvalidConfigException
     */
    private function attachBehaviorInternal($name, $behavior)
    {
        if (!($behavior instanceof Behavior)) {
            $behavior = Yii::createObject($behavior);
        }
        if (is_int($name)) {
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        } else {
            if (isset($this->_behaviors[$name])) {
                $this->_behaviors[$name]->detach();
            }
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        }

        return $behavior;
    }

    /**
     * Returns the named behavior object.
     * @param string $name the behavior name
     * @return null|Behavior the behavior object, or null if the behavior does not exist
     * @throws \yii\base\InvalidConfigException
     */
    public function getBehavior(string $name): ?Behavior
    {
        $this->ensureBehaviors();
        return isset($this->_behaviors[$name]) ? $this->_behaviors[$name] : null;
    }

    /**
     * Returns all behaviors attached to this component.
     * @return Behavior[] list of behaviors attached to this component
     * @throws \yii\base\InvalidConfigException
     */
    public function getBehaviors(): array
    {
        $this->ensureBehaviors();
        return $this->_behaviors;
    }

    /**
     * Attaches a behavior to this component.
     * This method will create the behavior object based on the given
     * configuration. After that, the behavior object will be attached to
     * this component by calling the [[Behavior::attach()]] method.
     * @param string $name the name of the behavior.
     * @param string|array|Behavior $behavior the behavior configuration. This can be one of the following:
     *
     *  - a [[Behavior]] object
     *  - a string specifying the behavior class
     *  - an object configuration array that will be passed to [[Yii::createObject()]] to create the behavior object.
     *
     * @return Behavior the behavior object
     * @throws \yii\base\InvalidConfigException
     * @see detachBehavior()
     */
    public function attachBehavior(string $name, $behavior): Behavior
    {
        $this->ensureBehaviors();
        return $this->attachBehaviorInternal($name, $behavior);
    }

    /**
     * Attaches a list of behaviors to the component.
     * Each behavior is indexed by its name and should be a [[Behavior]] object,
     * a string specifying the behavior class, or an configuration array for creating the behavior.
     * @param array $behaviors list of behaviors to be attached to the component
     * @throws \yii\base\InvalidConfigException
     * @see attachBehavior()
     */
    public function attachBehaviors(array $behaviors)
    {
        $this->ensureBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehaviorInternal($name, $behavior);
        }
    }

    /**
     * Detaches a behavior from the component.
     * The behavior's [[Behavior::detach()]] method will be invoked.
     * @param string $name the behavior's name.
     * @return null|Behavior the detached behavior. Null if the behavior does not exist.
     * @throws \yii\base\InvalidConfigException
     */
    public function detachBehavior(string $name): ?Behavior
    {
        $this->ensureBehaviors();
        if (isset($this->_behaviors[$name])) {
            $behavior = $this->_behaviors[$name];
            unset($this->_behaviors[$name]);
            $behavior->detach();
            return $behavior;
        }

        return null;
    }

    /**
     * Detaches all behaviors from the component.
     * @throws \yii\base\InvalidConfigException
     */
    public function detachBehaviors()
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $name => $behavior) {
            $this->detachBehavior($name);
        }
    }

    /**
     * Detaches an existing event handler from this component.
     *
     * This method is the opposite of [[on()]].
     *
     * Note: in case wildcard pattern is passed for event name, only the handlers registered with this
     * wildcard will be removed, while handlers registered with plain names matching this wildcard will remain.
     *
     * @param string $name event name
     * @param callable $handler the event handler to be removed.
     * If it is null, all handlers attached to the named event will be removed.
     * @return bool if a handler is found and detached
     * @throws \yii\base\InvalidConfigException
     * @see on()
     */
    public function off(string $name, $handler = null): ?bool
    {
        $this->ensureBehaviors();
        if (empty($this->_events[$name]) && empty($this->_eventWildcards[$name])) {
            return false;
        }
        if ($handler === null) {
            unset($this->_events[$name]);
            return true;
        }

        $removed = false;
        // plain event names
        if (isset($this->_events[$name])) {
            foreach ($this->_events[$name] as $i => $event) {
                if ($event[0] === $handler) {
                    unset($this->_events[$name][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $this->_events[$name] = array_values($this->_events[$name]);
                return true;
            }
        }

        return $removed;
    }

    /**
     * @param $name
     * @param $handler
     * @param null|array $data
     * @param bool $append
     */
    public function on($name, $handler, ?array $data = null, bool $append = true)
    {
        if ($append || empty($this->_events[$name])) {
            $this->_events[$name][] = [$handler, $data];
        } else {
            array_unshift($this->_events[$name], [$handler, $data]);
        }
    }

    /**
     * @param $name
     * @param \yii\base\Event|null $event
     */
    public function trigger($name, Event $event = null)
    {
        if (!empty($this->_events[$name])) {
            $eventHandlers = $this->_events[$name];
            if ($event === null) {
                $event = new Event();
            }
            if ($event->sender === null) {
                $event->sender = $this;
            }
            $event->handled = false;
            $event->name = $name;
            foreach ($eventHandlers as $handler) {
                $event->data = $handler[1];
                call_user_func($handler[0], $event);
                // stop further handling if the event is handled
                if ($event->handled) {
                    return;
                }
            }
        }

        // invoke class-level attached handlers
        Event::trigger($this, $name, $event);
    }
}
