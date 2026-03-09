<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_Record_Listener_Chain
 * this class represents a chain of different listeners,
 * useful for having multiple listeners listening the events at the same time
 *
 * @package     Doctrine
 * @subpackage  Record
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_Listener_Chain extends Doctrine_Access implements Doctrine_Record_Listener_Interface
{
	/**
	 * @var array $listeners        an array containing all listeners
	 */
	protected array $_listeners = [];
	
	/**
	 * @var array $_options        an array containing chain options
	 */
	protected array $_options = ['disabled' => false]; 
	
	/** 
	 * setOption 
	 * sets an option in order to allow flexible listener chaining 
	 * 
	 * @param mixed $name              the name of the option to set 
	 * @param mixed $value              the value of the option 
	 */ 
	public function setOption($name, $value = null) 
	{ 
		if (is_array($name)) { 
			$this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $name); 
		} else { 
			$this->_options[$name] = $value; 
		}
	} 
	
	/** 
	 * getOption 
	 * returns the value of given option 
	 * 
	 * @param string $name  the name of the option 
	 * @return mixed        the value of given option 
	 */ 
	public function getOption($name) 
	{ 
		if (isset($this->_options[$name])) { 
			return $this->_options[$name]; 
		} 
		
		return null; 
	}
	
	/**
	 * Get array of configured options
	 *
	 * @return array $options
	 */
	public function getOptions()
	{
		return $this->_options;
	}
	
	/**
	 * add
	 * adds a listener to the chain of listeners
	 *
	 * @param object $listener
	 * @param string $name
	 * @return void
	 */
	public function add($listener, $name = null)
	{
		if ( ! ($listener instanceof Doctrine_Record_Listener_Interface) &&
				! ($listener instanceof Doctrine_Overloadable)) {
			
			throw new Doctrine_EventListener_Exception("Couldn't add eventlistener. Record listeners should implement either Doctrine_Record_Listener_Interface or Doctrine_Overloadable");
		}
		if ($name === null) {
			$this->_listeners[] = $listener;
		} else {
			$this->_listeners[$name] = $listener;
		}
	}
	
	/**
	 * returns a Doctrine_Record_Listener on success
	 * and null on failure
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function get($key)
	{
		if ( ! isset($this->_listeners[$key])) {
			return null;
		}
		return $this->_listeners[$key];
	}
	
	/**
	 * set
	 *
	 * @param mixed $key
	 * @param Doctrine_Record_Listener $listener    listener to be added
	 * @return Doctrine_Record_Listener_Chain       this object
	 */
	public function set($key, $listener)
	{
		$this->_listeners[$key] = $listener;
	}
	
	public function preSerialize(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preSerialize', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preSerialize', $disabled, true))) {
					$listener->preSerialize($event);
				}
			}
		}
	}
	
	public function postSerialize(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postSerialize', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postSerialize', $disabled, true))) {
					$listener->postSerialize($event);
				}
			}
		}
	}
	
	public function preUnserialize(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preUnserialize', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preUnserialize', $disabled, true))) {
					$listener->preUnserialize($event);
				}
			}
		}
	}
	
	public function postUnserialize(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postUnserialize', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postUnserialize', $disabled, true))) {
					$listener->postUnserialize($event);
				}
			}
		}
	}
	
	public function preDqlSelect(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preDqlSelect', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preDqlSelect', $disabled, true))) {
					$listener->preDqlSelect($event);
				}
			}
		}
	}
	
	public function preSave(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preSave', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preSave', $disabled, true))) {
					$listener->preSave($event);
				}
			}
		}
	}
	
	public function postSave(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postSave', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postSave', $disabled, true))) {
					$listener->postSave($event);
				}
			}
		}
	}
	
	// [OV4] added
	public function postRelatedSave(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postRelatedSave', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postRelatedSave', $disabled, true))) {
					$listener->postRelatedSave($event);
				}
			}
		}
	}
	
	public function preDqlDelete(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preDqlDelete', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preDqlDelete', $disabled, true))) {
					$listener->preDqlDelete($event);
				}
			}
		}
	}
	
	public function preDelete(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preDelete', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preDelete', $disabled, true))) {
					$listener->preDelete($event);
				}
			}
		}
	}
	
	public function postDelete(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postDelete', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postDelete', $disabled, true))) {
					$listener->postDelete($event);
				}
			}
		}
	}
	
	public function preDqlUpdate(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preDqlUpdate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preDqlUpdate', $disabled, true))) {
					$listener->preDqlUpdate($event);
				}
			}
		}
	}
	
	public function preUpdate(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preUpdate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preUpdate', $disabled, true))) {
					$listener->preUpdate($event);
				}
			}
		}
	}
	
	public function postUpdate(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postUpdate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postUpdate', $disabled, true))) {
					$listener->postUpdate($event);
				}
			}
		}
	}
	
	public function preInsert(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preInsert', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preInsert', $disabled, true))) {
					$listener->preInsert($event);
				}
			}
		}
	}
	
	public function postInsert(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postInsert', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postInsert', $disabled, true))) {
					$listener->postInsert($event);
				}
			}
		}
	}
	
	public function preHydrate(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preHydrate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preHydrate', $disabled, true))) {
					$listener->preHydrate($event);
				}
			}
		}
	}
	
	public function postHydrate(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postHydrate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postHydrate', $disabled, true))) {
					$listener->postHydrate($event);
				}
			}
		}
	}
	
	public function preValidate(Doctrine_Event $event): void
	{ 
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('preValidate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('preValidate', $disabled, true))) {
					$listener->preValidate($event);
				}
			}
		}
	}
	
	public function postValidate(Doctrine_Event $event): void
	{
		$disabled = $this->getOption('disabled');
		
		if ($disabled !== true && ! (is_array($disabled) && in_array('postValidate', $disabled, true))) {
			foreach ($this->_listeners as $listener) {
				$disabled = $listener->getOption('disabled');
				
				if ($disabled !== true && ! (is_array($disabled) && in_array('postValidate', $disabled, true))) {
					$listener->postValidate($event);
				}
			}
		}
	}
}