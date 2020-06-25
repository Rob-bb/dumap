<?php
include '../config.php';
include '../library/vars.php';
include '../library/global.php';

//session_start();  // This is handled in global.php now

if(session('access_token')) {

    //CHECK SESSION TIMEOUT
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600)) {
        // last request was more than 10 minutes ago
        session_unset();
        session_destroy();
        session_write_close();
        setcookie(session_name(),'',0,'/');
        session_regenerate_id(true);
        header("Location: /");
        die();
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

    $user = apiRequest($apiURLBase);
    $guilds = apiRequest($apiURLGuilds);
    $guildmember = apiBotRequest($apiURLGuildMember, $user->id);
    $data = json_decode($guildmember);
    $blacklistfile = file_get_contents('./data/blacklist.json');
    $blacklist = json_decode($blacklistfile, false);

    $isbanned = false;
    foreach($blacklist as $banned){
        if($banned->id == $user->id) {
            $isbanned = true;
        }
    }

    $found = FALSE;
    if($isbanned == false) {
        foreach ($data->roles as $field) {
            if ($field == ALPHA_AUTHORIZED_ROLE_ID || $field == NQSTAFF_ROLE_ID) {
                $found = TRUE;
                // Site begins here
                echo <<<EOL

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>
   Dual Universe Lua Codex
    </title>
    <link href="../css/web_codex.css" rel="stylesheet" type="text/css">
  </head>
  <body id="web_codex">
    <div class="main_menu_section" id="main_menu_encyclopedia">
      <div class="codex_navigation_wrapper">
        <div class="codex_searchbox" id="web_codex_searchbox">
        </div>
        <div class="codex_navigationDrilldown" id="web_codex_nav">
        </div>
      </div>
      <div class="help_screen_content" id="help_screen_widget_content">
        <!--======== START INDIVIDUAL PAGES ========-->
        <section id="bm_scripting" title="Scripting">
          <h1>
            Dual Universe Lua Scripting
          </h1>
          <section id="bm_scripting_introduction" title="Introduction">
            <h2>
              Introduction
            </h2>
            <p>
              In Dual Universe, you can script interactions between Elements using Lua. The principle is the following: you can plug Elements into a Control Unit "CONTROL" slots, and each of the plugged Element will present itself as a Lua object capable to emit events and execute functions. The name of the slot is simply the name of the Lua object. The Control Unit has a dedicated "Edit Lua" window where you can then associate a piece of Lua code to any event emitted by one of your plugged Element. This code will be executed when the event occurs. Inside the Lua script, you can use the functions offered by the plugged Elements to generate any behavior you like. These functions are simply methods of the Lua object that represents the slot where your Element is plugged into.
            </p>
          </section>
          <section id="bm_scripting_concepts" title="Concepts">
            <h2>
              Concepts
            </h2>
            <p>
              To script Elements in Lua, here are the necessary steps:
            </p>
            <ul>
              <li>
                Identify the Control Unit that you want to host your Lua scripting. You Lua code will run when you activate this Control Unit.
              </li>
              <li>
                Plug all the Elements that you want to script together inside the Control Unit, using CONTROL links. You can simply click the Control Unit and then the desired plugged Element, this will pick the first free CONTROL plug in your Control Unit. You may want to selectively pick a particular plug, in that case you need to right-click on the Control Unit first and select the desired plug in the menu.
              </li>
              <li>
                Once all the Elements are plugged, open the "Edit Lua" window from the context menu of the Control Unit. This will open the "Control Unit Editor".
              </li>
              <li>
                The Control Unit Editor is composed of three columns. The first column lists all the slots available, with the corresponding Element plugged inside them. Each Slot correspond to one of the CONTROL plug of the Control Unit.
                <p>
                  Note that there are some predefined slots:
                </p>
                <ul>
                  <li>
                    System: the System DPU, to access things like keystrokes, updates, timers. See the doc below.
                  </li>
                  <li>
                    Unit: this is the Control Unit itself
                  </li>
                  <li>
                    Library: contains useful functions that are implemented in C++ for performance reasons
                  </li>
                </ul>
                <p>
                  The rest of the slots are the slots you used to plug your Elements. You can rename them to help you remember who is what.
                </p>
              </li>
              <li>
                Select one slot, for example the System slot.
              </li>
              <li>
                In the middle column (which is initially empty), you can define event handlers to react to events associated to the slot you have selected.
              </li>
              <li>
                Add an event, for example "actionStart", and select the "forward" action in the dropdown menu that appears when you try to edit the event argument.
              </li>
              <li>
                When you click on an event handler, the third column will display the associated Lua code that must be run when this event occurs.
              </li>
              <li>
                The Lua code can use any of the functions of any slot, using the syntax: slotname.function(...)
              </li>
              <li>
                The documentation below details all the functions and events available for all the type of Element in the game
              </li>
            </ul>
          </section>
          <section id="bm_scripting_piloting" title="Physics scripting">
            <h2>
              Physics scripting, how is your ship flying?
            </h2>
            <p>
              Piloting a ship is a complex topic, a bit like... rocket science actually! We tried to simplify it to a few core concepts. The ship will move because it has some engines attached to it, and engines are capable to exert a force on the body of the ship. To be more precise, there are two things an engine can generate:
            </p>
            <ul>
              <li>
                Forces: they actually push your ship and make it move in a given direction
              </li>
              <li>
                Torques: they make your ship rotate on itself
              </li>
            </ul>
            <p>
              To simplify the control problem, we made a first move by defining engines that can do "torque" only, called the "Adjustors", vs engines that can do "force" only, basically the other engines. Hovercraft engines are the only engines capable to produce force and torque at the same time.
            </p>
            <p>
              You can control the thrust of each individual engine in your ship by plugging them in the Control Unit and using the setThrust method. However, this can become tedious as the number of engines grow, and it is quite difficult to calculate exactly what thrust to apply to which engine if you want to control the overall global cumulative effect. So, we introduce a cool notion to simplify the process of controlling your engines: grouping.
            </p>
            <p>
              Grouping engines is done via a tagging mechanism, that you can access by right-clicking an engine and setting its associated tags. By default, all engines are tagged with "all" and some other tags indicating their typical role in the ship control. The default tagging is the following:
            </p>
            <ul>
              <li>
                Adjustors: all, torque
              </li>
              <li>
                Hovercraft: all, vertical, torque
              </li>
              <li>
                Vertical Boosters: all, vertical
              </li>
              <li>
                Air brake or Retro Engines: all, brake
              </li>
              <li>
                All the others: all, horizontal
              </li>
            </ul>
            <p>
              These are the default, and you can freely modify them and add you custom groups.
            </p>
            <p>
              Once you have a group of engines, you have a facility within your Control Unit to address them as a whole, just as if they were one single engine, and assign them a given linear and angular acceleration. The system will then calculate the corresponding force and torque that are needed to produce these accelerations, and figure out a thrust assignment for all the engines in the group, so that the end result will be equal or as close as possible to the requested command. This is the "setEngineCommand" method, available in the Control Unit slot (called "unit").
            </p>
            <p>
              Using this facility, the auto configurator that generates a Lua script for you the first time you enter into a cockpit will typically produce Lua code of this form:
            </p>
            <fieldset>
              control.setEngineCommand("vertical,torque", acceleration, angularAcceleration)
              <br>
              control.setEngineCommand("horizontal", forwardAcceleration, nullvector)
              <br>
              control.setEngineCommand("brake", brake, nullvector)
            </fieldset>
            <p>
              The linear acceleration in the horizontal or vertical direction, as well as the angular acceleration, are the result of a computation that is done in the Navigator Lua code, which you may freely modify (it's the ControlCommand.lua file, which is loaded whenever you start a Control Unit), and which defines how controlling of your ship by pressing keys will affect the desired acceleration requested.
            </p>
            <p>
              All these calculation and the final call to the setEngineCommand method are done in the "flush" event of the System slot. Flush is called several times per frame to calculate the physics simulation. You should never put anything else but physics-related calculation in flush. Anything else this is gameplay related, like connecting buttons or displaying things on screens, should be done in the "update" event, which is called at each frame rendering.
            </p>
          </section>
          <section id="bm_scripting_events" title="System events">
            <h2>
              System events and methods
            </h2>
            <p>
              The System DPU, available in the "system" slot is a crucial part of your Lua programming. This virtual element (it is always plugged and always there) will send various events that you can catch to orchestrate your Lua scripting.
            </p>
            <p>
              There are three fundamental ways to get events are regular intervals from System:
            </p>
            <ul>
              <li>
                update: this event is triggered at each frame (its frequency will depend on the framerate)
              </li>
              <li>
                flush: this event is triggered at each step of the physics calculation, usually more than once per frame. Be careful to limit its use to setting engine thrusts, for example with the setEngineCommand method.
              </li>
              <li>
                tick(n): this event is repeatedly triggered whenever the timer 'n' is reaching its duration. You can create a timer with index "n" and with a given duration using the setTimer(n, duration) method in System. The duration is expressed in seconds, and the event will tick at quasi-regular intervals, independently of the framerate if the duration is larger that the typical frame duration.
              </li>
            </ul>
            <p>
              The other important event type that System is managing are keystroke events. We call these "actions" to make it independent on the particular key binding that will be associated to it. System then defines three types of events associated to an action:
            </p>
            <ul>
              <li>
                actionStart(action): this event is triggered when the action starts (key pressed down)
              </li>
              <li>
                actionStop(action): this event is triggered when the action stops (key released)
              </li>
              <li>
                actionLoop(action): this event is triggered at each frame as long as the key is pressed (think of it as a filter on the "update" event)
              </li>
            </ul>
            <p>
              Actions are referred to by their name, and the Control Unit Editor will pop a list of available actions to choose from whenever you click on the argument of an action event.
            </p>
          </section>
          <section id="bm_scripting_inside_lua" title="Event filter">
            <h2>
              Lua code associated to an event filter
            </h2>
            <p>
              Inside the code window that is associated to an event filter on a given slot, you can type in any Lua code you like. There are limits in memory and CPU usage however, as well as in the total size of the code (currently 10Ko compressed).
            </p>
            <p>
              Note that the Lua interpreter that runs the Lua code is specific to each Control Unit, so there is not automatic sharing of the memory between two running Control Units. You may however exchange variable between different event handlers, simply define them without the "local" keyword and they will become global variables.
            </p>
            <p>
              The Lua code will typically refer to some of the slots in the Control Unit to call methods associated to the elements that are plugged in these slots. Suppose you have a "screen" slot where you have plugged a Screen Unit, you may write code of the form:
            </p>
            <fieldset>
              screen.setCenteredText("Hello World")
            </fieldset>
            <p>
              To know what methods and what events are available for all the different Elements in the game, simply refer to the documentation below.
            </p>
          </section>
          <section id="bm_exporting_lua_vars" title="Export variables">
            <h2>
              How to expose some values outside of Lua editor
            </h2>
            <p>
              You can edit Lua variable values without opening the Lua editor. This can be handy to expose some configuration variables. They only have to be declared with the "export" keyword:
            </p>
            <fieldset>
              rotationSpeed = 2 --export
              <br>
              local rotationSpeed = 2 --export: rotation speed in rad/s
            </fieldset>
            <p>
              Then, you can right click on your ControlUnit and select the "Edit Lua Parameters" action to modify those exported values.
            </p>
          </section>
          <section id="bm_scripting_element_API" title="Element API">
            <h2>
              Element API
            </h2>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Generic_Element" title="Generic Element">
                Generic Element
              </h3>
              <p class="elementAPI_description">
                All elements share the same generic methods described below
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  show()
                </h4>
                <p class="elementAPI_method_description">
                  show the element widget in the in-game widget stack
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  hide()
                </h4>
                <p class="elementAPI_method_description">
                  hide the element widget in the in-game widget stack
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getData()
                </h4>
                <p class="elementAPI_method_description">
                  get element data as json
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        data as json.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getDataId()
                </h4>
                <p class="elementAPI_method_description">
                  get element data id
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        data id. "" if invalid.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWidgetType()
                </h4>
                <p class="elementAPI_method_description">
                  get widget type compatible with the element data
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        widget type. "" if invalid.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getIntegrity()
                </h4>
                <p class="elementAPI_method_description">
                  the element integrity between  0 and 100
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        0 = element fully destroyed, 100 = element fully functional
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getHitPoints()
                </h4>
                <p class="elementAPI_method_description">
                  the element current hit points (0 = destroyed)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the hitpoints. 0 = element fully destroyed
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMaxHitPoints()
                </h4>
                <p class="elementAPI_method_description">
                  the element maximal hit points when it's fully functional
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the max hitpoints of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getId()
                </h4>
                <p class="elementAPI_method_description">
                  a construct-unique id for the element
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the element id
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMass()
                </h4>
                <p class="elementAPI_method_description">
                  the mass of the element
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        kg
                      </td>
                      <td>
                        the mass of the element (includes the included items mass when the element is a container)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementClass()
                </h4>
                <p class="elementAPI_method_description">
                  the class of the element
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the class name of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setSignalIn(plug, state)
                </h4>
                <p class="elementAPI_method_description">
                  set the value of a signal in the specified IN plug of the element Standard plug names are composed with the following syntax =&gt; direction-type-index where 'direction' can be IN or OUT, 'type' is one of the following =&gt; ITEM, FUEL, ELECTRICITY, SIGNAL, HEAT, FLUID, CONTROL, and 'index' is a number between 0 and the total number of plugs of the given type in the given direction. Some plugs have special names like "on" or "off" for the Manual Swith Unit, just check in-game for the plug names if you have a doubt.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        plug
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the plug name, of the form IN-SIGNAL-index
                      </td>
                    </tr>
                    <tr>
                      <td>
                        state
                      </td>
                      <td>
                        0/1
                      </td>
                      <td>
                        the plug signal state
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getSignalIn(plug)
                </h4>
                <p class="elementAPI_method_description">
                  return the value of a signal in the specified IN plug of the element Standard plug names are composed with the following syntax =&gt; direction-type-index where 'direction' can be IN or OUT, 'type' is one of the following =&gt; ITEM, FUEL, ELECTRICITY, SIGNAL, HEAT, FLUID, CONTROL, and 'index' is a number between 0 and the total number of plugs of the given type in the given direction. Some plugs have special names like "on" or "off" for the Manual Swith Unit, just check in-game for the plug names if you have a doubt.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        plug
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the plug name, of the form IN-SIGNAL-index
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0/1
                      </td>
                      <td>
                        the plug signal state
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getSignalOut(plug)
                </h4>
                <p class="elementAPI_method_description">
                  return the value of a signal in the specified OUT plug of the element Standard plug names are composed with the following syntax =&gt; direction-type-index where 'direction' can be IN or OUT, 'type' is one of the following =&gt; ITEM, FUEL, ELECTRICITY, SIGNAL, HEAT, FLUID, CONTROL, and 'index' is a number between 0 and the total number of plugs of the given type in the given direction. Some plugs have special names like "on" or "off" for the Manual Swith Unit, just check in-game for the plug names if you have a doubt.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        plug
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the plug name, of the form OUT-SIGNAL-index
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0/1
                      </td>
                      <td>
                        the plug signal state
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Container_Unit" title="Container Unit">
                Container Unit
              </h3>
              <p class="elementAPI_description">
                Stores items
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getItemsMass()
                </h4>
                <p class="elementAPI_method_description">
                  returns the container content mass (the sum of the mass of all the items it contains)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        kg
                      </td>
                      <td>
                        the total container content mass, excluding the container own self mass
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getSelfMass()
                </h4>
                <p class="elementAPI_method_description">
                  returns the container self mass
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        kg
                      </td>
                      <td>
                        the container self mass, as if it where empty
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Control_Unit" title="Control Unit">
                Control Unit
              </h3>
              <p class="elementAPI_description">
                Control Units come in various forms: cockpits, programming boards, Emergency Control Units, etc.A Control Unit stores a set of Lua scripts that can be used to control the Elements that are plugged on its CONTROL plugs.Kinematics Control Units like cockpit or commander seats are also capable of controlling the ship's engines via theupdateICC method.
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  exit()
                </h4>
                <p class="elementAPI_method_description">
                  stops the Control Unit Lua code and exit. Warning: calling this might cause your ship to fall from the sky, use it with care. It is typically used in the coding of Emergency Control Unit scripts to stop the control once the ECU thinks that the ship is safely landed.
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setTimer(timerTagId, period)
                </h4>
                <p class="elementAPI_method_description">
                  setup a timer with a given tag id with a given period. This will start to trigger the 'tick' event with the corresponding id as an argument, to help your identify what is ticking when.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        timerTagId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the id of the timer, as a string, which will be used in the 'tick' event to identify this particular timer
                      </td>
                    </tr>
                    <tr>
                      <td>
                        period
                      </td>
                      <td>
                        second
                      </td>
                      <td>
                        the period of the timer, in seconds. The time resolution is limited by the framerate here, so you cannot set arbitrarily fast timers.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  stopTimer(timerTagId)
                </h4>
                <p class="elementAPI_method_description">
                  stop the timer with the given id
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        timerId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the id of the timer to stop, as a string
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getAtmosphereDensity()
                </h4>
                <p class="elementAPI_method_description">
                  returns the local atmosphere density, between 0 and 1
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        the atmosphere density (0 = in space)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getClosestPlanetInfluence()
                </h4>
                <p class="elementAPI_method_description">
                  returns the closest planet influence, between 0 and 1
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        the closest planet influence. 0 = in space, 1 = on the ground
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMasterPlayerRelativePosition()
                </h4>
                <p class="elementAPI_method_description">
                  return the relative position (in world coordinates) of the player currently runnning the Control Unit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        relative position in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMasterPlayerId()
                </h4>
                <p class="elementAPI_method_description">
                  return the id of the player currently runnning the Control Unit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        id of the player running the Control Unit
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setEngineCommand(taglist, acceleration, angularAcceleration)
                </h4>
                <p class="elementAPI_method_description">
                  automatically assign the engines within the taglist
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        to result in the given acceleration and angularaAcceleration provided. Can only be called within the Syste
                      </td>
                      <td>
                        to result in the given acceleration and angularaAcceleration provided. Can only be called within the Syste
                      </td>
                      <td>
                        flush event.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        taglist
                      </td>
                      <td>
                        csv
                      </td>
                      <td>
                        comma separated list of tags. You can set tags directly on the engines in the right-click menu.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        acceleration
                      </td>
                      <td>
                        m/s2
                      </td>
                      <td>
                        the desired acceleration expressed in world coordinates
                      </td>
                    </tr>
                    <tr>
                      <td>
                        angularAcceleration
                      </td>
                      <td>
                        rad/s2
                      </td>
                      <td>
                        the desired angular acceleration expressed in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setEngineThrust(taglist, thrust)
                </h4>
                <p class="elementAPI_method_description">
                  force the thurst value for all the engines within the taglist
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        taglist
                      </td>
                      <td>
                        csv
                      </td>
                      <td>
                        comma separated list of tags. You can set tags directly on the engines in the right-click menu.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        thrust
                      </td>
                      <td>
                        N
                      </td>
                      <td>
                        the desired thrust in Newton (note that for boosters, any non zero value here will set them to 100%)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setThrottle(throttle)
                </h4>
                <p class="elementAPI_method_description">
                  set the value of throttle in the cockpit, which will be displayed in the cockpit widget when flying
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        throttle
                      </td>
                      <td>
                        -1..1
                      </td>
                      <td>
                        the value of the throttle position: -1 = full reverse, 1 = full forward
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getThrottle()
                </h4>
                <p class="elementAPI_method_description">
                  get the value of throttle in the cockpit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -1..1
                      </td>
                      <td>
                        return the value of the throttle position: -1 = full reverse, 1 = full forward
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  tick(timerId)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when the timer with id 'timerId' is ticking
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        timerId
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the Id (int) of the timer that just ticked (see setTimer to set a timer with a given id)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_DataBank_Unit" title="DataBank Unit">
                DataBank Unit
              </h3>
              <p class="elementAPI_description">
                Stores key/value pairs in a persistent way
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  clear()
                </h4>
                <p class="elementAPI_method_description">
                  clear the data bank
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getNbKeys()
                </h4>
                <p class="elementAPI_method_description">
                  returns how many keys are stored inside the data bank
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the number of keys
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getKeys()
                </h4>
                <p class="elementAPI_method_description">
                  returns all the keys in the data bank
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        json
                      </td>
                      <td>
                        the key list, as json sequence
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  hasKey(key)
                </h4>
                <p class="elementAPI_method_description">
                  returns 1 if the key is present in the data bank, 0 otherwise
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        bool
                      </td>
                      <td>
                        1 if the key exists and 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setStringValue(key,val)
                </h4>
                <p class="elementAPI_method_description">
                  stores a string value at the given key
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        key
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key used to store the value
                      </td>
                    </tr>
                    <tr>
                      <td>
                        val
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the value, as a string
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getStringValue(key)
                </h4>
                <p class="elementAPI_method_description">
                  returns value stored in the given key as a string
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        key
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key used to retrieve the value
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the value as a string
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setIntValue(key,val)
                </h4>
                <p class="elementAPI_method_description">
                  stores an integer value at the given key
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        key
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key used to store the value
                      </td>
                    </tr>
                    <tr>
                      <td>
                        val
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the value, as an integer
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getIntValue(key)
                </h4>
                <p class="elementAPI_method_description">
                  returns value stored in the given key as an integer
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        key
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key used to retrieve the value
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the value as an integer
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setFloatValue(key,val)
                </h4>
                <p class="elementAPI_method_description">
                  stores a floating number value at the given key
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        key
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key used to store the value
                      </td>
                    </tr>
                    <tr>
                      <td>
                        val
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        the value, as a floating number
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getFloatValue(key)
                </h4>
                <p class="elementAPI_method_description">
                  returns value stored in the given key as a floating number
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        key
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key used to retrieve the value
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        the value as a floating number
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Door_Unit" title="Door Unit">
                Door Unit
              </h3>
              <p class="elementAPI_description">
                A door that can be opened or closed
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  open the door
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  close the door
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the door
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the door
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the door is opened, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Engine_Unit" title="Engine Unit">
                Engine Unit
              </h3>
              <p class="elementAPI_description">
                An engine is capable to produce a force and/or a torque to moveyour construct
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  start the engine at full power (works only when run inside a cockit or remote controller)
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  stops the engine (works only when run inside a cockit or remote controller)
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the engine
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the anti-G generator
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the anti-G generator is started, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setThrust(thrust)
                </h4>
                <p class="elementAPI_method_description">
                  set the engine thrust between 0 and maxThrust
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        thrust
                      </td>
                      <td>
                        Newton
                      </td>
                      <td>
                        the engine thrust
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMaxThrustBase()
                </h4>
                <p class="elementAPI_method_description">
                  returns the maximal thrust the engine can deliver in principle, in optimal conditions. Note that the actual maxThrust will most of the time be less that maxThrustBase.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        Newton
                      </td>
                      <td>
                        the base max thrust
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getCurrentMaxThrust()
                </h4>
                <p class="elementAPI_method_description">
                  returns the maximal thrust the engine can deliver at the moment, which might depend on various conditions like atmospheric density, obstruction, orientation, etc. The actual thrust will be anything below this maxThrust, which defines the current max capability of the engine.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        Newton
                      </td>
                      <td>
                        the current max thrust
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getCurrentMinThrust()
                </h4>
                <p class="elementAPI_method_description">
                  returns the minimal thrust the engine can deliver at the moment (can be more than zero), which might depend on various conditions like atmospheric density, obstruction, orientation, etc. Most of the time, this will be 0 but it can be greater than 0, in particular for Ailerons, in which case the actual thrust will be at least equal to minThrust.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        Newton
                      </td>
                      <td>
                        the current min thrust
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMaxThrustEfficiency()
                </h4>
                <p class="elementAPI_method_description">
                  returns the ratio between the current MaxThrust and the base MaxThrust
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        usually 1 but can be lower for certain engines
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getThrust()
                </h4>
                <p class="elementAPI_method_description">
                  returns the current thrust level of the engine
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        Newton
                      </td>
                      <td>
                        the thrust the engine is currently delivering
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  torqueAxis()
                </h4>
                <p class="elementAPI_method_description">
                  returns the engine torque axis
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the torque axis in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  thrustAxis()
                </h4>
                <p class="elementAPI_method_description">
                  returns the engine thrust direction
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the engine thrust direction in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getDistanceToObstacle()
                </h4>
                <p class="elementAPI_method_description">
                  returns the distance to the first object detected in the direction of the thrust
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the distance to the first obstacle
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  isOutOfFuel()
                </h4>
                <p class="elementAPI_method_description">
                  is the engine out of fuel?
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        bool
                      </td>
                      <td>
                        true when there is no fuel left, false otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getCurrentFuelRate()
                </h4>
                <p class="elementAPI_method_description">
                  the engine rate of fuel consumption per Newton delivered per second
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m3/(N.s)
                      </td>
                      <td>
                        how much L of fuel per Newton per second
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getFuelRateEfficiency()
                </h4>
                <p class="elementAPI_method_description">
                  returns the ratio between the current fuel rate and the theoretical nominal fuel rate
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        usually 1 but can be higher for certain engines at certain speeds
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getT50()
                </h4>
                <p class="elementAPI_method_description">
                  the time needed for the engine to reach 50% of its maximal thrust (all engines do not instantly reach the thrust they is set for them, but they can take time to "warm up" to the final value)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        second
                      </td>
                      <td>
                        the time to half thrust
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  isObstructed()
                </h4>
                <p class="elementAPI_method_description">
                  is the engine exhaust obstructed by some element or voxel material? If yes, it will stop working or may work randomly in an instable way, and you should probably fix your design.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        bool
                      </td>
                      <td>
                        true when the engine is obstructed
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getTags()
                </h4>
                <p class="elementAPI_method_description">
                  tags of the engine
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        csv
                      </td>
                      <td>
                        tags of the engine, in a csv string
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setTags(tags)
                </h4>
                <p class="elementAPI_method_description">
                  set the tags of the engine
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        tags
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        csv string of the tags
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getFuelConsumption()
                </h4>
                <p class="elementAPI_method_description">
                  the current rate of fuel consumption
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m3/s
                      </td>
                      <td>
                        how much m3 of fuel per units of time
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Fireworks_Unit" title="Fireworks Unit">
                Fireworks Unit
              </h3>
              <p class="elementAPI_description">
                A unit capable to launch fireworks that are stored in the attached container
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  fire the firework
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setExplosionDelay(t)
                </h4>
                <p class="elementAPI_method_description">
                  set the delay before the launched fireworks explodes. Max=5s
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        t
                      </td>
                      <td>
                        second
                      </td>
                      <td>
                        the delay before explosion
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setLaunchSpeed(v)
                </h4>
                <p class="elementAPI_method_description">
                  set the speed at which the firework will be launched (impacts its altitude, depending on the local gravity). Max=200m/s
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        v
                      </td>
                      <td>
                        m/s
                      </td>
                      <td>
                        the launch speed
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setType(type)
                </h4>
                <p class="elementAPI_method_description">
                  set the type of launched firework (will affect which firework is picked in the attached container)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        type
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        0=BALL, 1=PALMTREE, 2=RING, 3=SHOWER
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setColor(color)
                </h4>
                <p class="elementAPI_method_description">
                  set the color of the launched firework (will affect which firework is picked in the attached container)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        color
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        0=BLUE, 1=GOLD, 2=GREEN, 3=PURPLE, 4=RED, 5=SILVER
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Force_Field_Unit" title="Force Field Unit">
                Force Field Unit
              </h3>
              <p class="elementAPI_description">
                A forcefield to create an uncrossable energy barrier
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  activate the field
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  deactivate the field
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the field
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the field is active, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_LandingGear_Unit" title="LandingGear Unit">
                LandingGear Unit
              </h3>
              <p class="elementAPI_description">
                A landing gear that can be opened or closed
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  open the landing gear
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  close the landing gear
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the gear
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the landing gear
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the landing gear is opened, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Light_Unit" title="Light Unit">
                Light Unit
              </h3>
              <p class="elementAPI_description">
                Emit a source of light
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  switches the light on
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  switches the light off
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the light
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the light
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the light is on, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Anti_Gravity_Generator_Unit" title="Anti Gravity Generator Unit">
                Anti Gravity Generator Unit
              </h3>
              <p class="elementAPI_description">
                Generates graviton condensates to power Anti Gravity Pulsors.
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  start the anti-G generator
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  stop the anti-G generator
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the anti-G generator
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the anti-G generator
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the anti-G generator is started, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setBaseAltitude(altitude)
                </h4>
                <p class="elementAPI_method_description">
                  set the base altitude for the antigravity field
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        altitude
                      </td>
                      <td>
                        m
                      </td>
                      <td>
                        the desired altitude. Will be reached with a slow acceleration (not instantaneous)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Industry_Unit" title="Industry Unit">
                Industry Unit
              </h3>
              <p class="elementAPI_description">
                Can mass-produce produce any item/element
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  start()
                </h4>
                <p class="elementAPI_method_description">
                  start the production, and it will run unless it is stopped or the input resources run out.
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  softStop()
                </h4>
                <p class="elementAPI_method_description">
                  end the job and stop. The production keeps going until it is complete, then it switches to "STOPPED" status. Unless the output container is full, then it switches to "JAMMED"
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  hardStop()
                </h4>
                <p class="elementAPI_method_description">
                  stop right now. The resources are given back to the input container. If there is no room enough in the input containers the stop is skipped if allowIngredientLoss is set to 0 or stop with the loss of the ingredients if set to 1
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        allowIngredientLoss
                      </td>
                      <td>
                        0/1
                      </td>
                      <td>
                        0 = forbid loss , 1 = enable loss
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getStatus()
                </h4>
                <p class="elementAPI_method_description">
                  get the status of the industry
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the status of the industry can be : STOPPED, RUNNING, JAMMED_MISSING_INGREDIENT, JAMMED_OUTPUT_FULL, JAMMED_NO_OUTPUT_CONTAINER
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getCycleCountSinceStartup()
                </h4>
                <p class="elementAPI_method_description">
                  get the count of completed cycle since the player started the unit.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the count of completed cycle since startup
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getEfficiency()
                </h4>
                <p class="elementAPI_method_description">
                  get the efficiency of the industry
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        the efficiency rate between 0 and 1
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getUptime()
                </h4>
                <p class="elementAPI_method_description">
                  get the time elapsed in seconds since the player started the unit for the latest time.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        s
                      </td>
                      <td>
                        the time elapsed in seconds
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  completed()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when the industry unit has completed a run
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  statusChanged(status)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when the industry status has changed
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        status
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the status of the industry can be : STOPPED, RUNNING, JAMMED_MISSING_INGREDIENT, JAMMED_OUTPUT_FULL, JAMMED_NO_OUTPUT_CONTAINER
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Counter_Unit" title="Counter Unit">
                Counter Unit
              </h3>
              <p class="elementAPI_description">
                Cycle its output signal over a set of n plugs, incrementing the activate plug by one step at each impulsereceived on its inPlug.
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getCounterState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the rank of the currently active out plug
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        the index of the active plug
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  next()
                </h4>
                <p class="elementAPI_method_description">
                  moves the counter one step further (equivalent to signal received on the in plug)
                </p>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Emitter_Unit" title="Emitter Unit">
                Emitter Unit
              </h3>
              <p class="elementAPI_description">
                This unit is capable of emitting messages on channels
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  send(channel,message)
                </h4>
                <p class="elementAPI_method_description">
                  send a message on the given channel
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        channel
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the channel name
                      </td>
                    </tr>
                    <tr>
                      <td>
                        message
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the message to transmit
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getRange()
                </h4>
                <p class="elementAPI_method_description">
                  returns the emitter range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the range
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Receiver_Unit" title="Receiver Unit">
                Receiver Unit
              </h3>
              <p class="elementAPI_description">
                Receives messages on given channels
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getRange()
                </h4>
                <p class="elementAPI_method_description">
                  returns the receiver range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the range
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  receive(channel,message)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when a message is received on any channel
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        channel
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the channel, can be used as a filter
                      </td>
                    </tr>
                    <tr>
                      <td>
                        message
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the message received
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Core_Unit" title="Core Unit">
                Core Unit
              </h3>
              <p class="elementAPI_description">
                This is the heart of your Construct. It represents it and gives access to all construct-related information.
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructMass()
                </h4>
                <p class="elementAPI_method_description">
                  returns the mass of the Construct
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        kg
                      </td>
                      <td>
                        the mass of the construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructIMass()
                </h4>
                <p class="elementAPI_method_description">
                  returns the inertial mass of the Construct, calculated as 1/3 of the trace of the inertial tensor.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        kg*m2
                      </td>
                      <td>
                        the inertial mass of the construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructCrossSection()
                </h4>
                <p class="elementAPI_method_description">
                  returns the construct cross section surface in the current direction of movement
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m2
                      </td>
                      <td>
                        the construct surface exposed in the current direction of movement
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMaxKinematicsParameters()
                </h4>
                <p class="elementAPI_method_description">
                  returns the construct max kinematics parameters in both atmo and space range, in Newton. Kinematics parameters designate here the maximal positive and negative base force the construct is capable to produce along the forward axis, as defined by the Core Unit or the Gyro Unit, if active. In practice, this is giving you an estimate of the maximum thrust your ship is capable to produce in space or in atmosphere, as well as the max reverse thrust. These are theoretical estimates and correspond to the addition of the maxThrustBase along the corresponding axis. It might not reflect the accurate current max thrust capacity of your ship, which depends on various local conditions (atmospheric density, orientation, obstruction, engine damage, etc). This is typically used in conjunction with the Control Unit throttle to setup the desired forward acceleration.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec4,Newton
                      </td>
                      <td>
                        the kinematics parameters in that order =&gt; atmoRange.FMaxPlus, atmoRange.FMaxMinus, spaceRange.FMaxPlus, spaceRange.FMaxMinus
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldPos()
                </h4>
                <p class="elementAPI_method_description">
                  returns the world position of the construct
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz world coordinates of the construct core unit position
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructId()
                </h4>
                <p class="elementAPI_method_description">
                  returns the construct unique id
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the unique id. Can be used with database.getConstruct to retrieve info about the construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldAirFrictionAngularAcceleration()
                </h4>
                <p class="elementAPI_method_description">
                  returns the accelerationTorque generated by air resistance
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz world acceleration torque generated by air resistance
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldAirFrictionAcceleration()
                </h4>
                <p class="elementAPI_method_description">
                  returns the acceleration generated by air resistance
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz world acceleration generated by air resistance
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  spawnNumberSticker(nb,x,y,z,orientation)
                </h4>
                <p class="elementAPI_method_description">
                  spawns a number sticker in the 3D world, with coordinates relative to the construct.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        nb
                      </td>
                      <td>
                        0-9
                      </td>
                      <td>
                        the number to display
                      </td>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the x coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the y coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        z
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the z coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        orientation
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        orientation of the number. Possible values are "front", "side"
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        an index that can be used later to delete or move the item, -1 if error or maxnumber reached
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  spawnArrowSticker(x,y,z,orientation)
                </h4>
                <p class="elementAPI_method_description">
                  spawns an arrow sticker in the 3D world, with coordinates relative to the construct.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the x coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the y coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        z
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the z coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        orientation
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        orientation of the arrow. Possible values are "up", "down", "north", "south", "east", "west"
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        an index that can be used later to delete or move the item, -1 if error or max number reached
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deleteSticker(index)
                </h4>
                <p class="elementAPI_method_description">
                  delete the referenced sticker.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        index
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        index of the sticker to delete
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        1 in case of success, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  moveSticker(index,x,y,z)
                </h4>
                <p class="elementAPI_method_description">
                  move the referenced sticker.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the x coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the y coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        z
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the z coordinate in the construct. 0 = center
                      </td>
                    </tr>
                    <tr>
                      <td>
                        index
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        index of the sticker to move
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        1 in case of success, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  rotateSticker(index,angle_x,angle_y,angle_z)
                </h4>
                <p class="elementAPI_method_description">
                  rotate the referenced sticker.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        angle_x
                      </td>
                      <td>
                        deg
                      </td>
                      <td>
                        rotation along the x axis
                      </td>
                    </tr>
                    <tr>
                      <td>
                        angle_y
                      </td>
                      <td>
                        deg
                      </td>
                      <td>
                        rotation along the y axis
                      </td>
                    </tr>
                    <tr>
                      <td>
                        angle_z
                      </td>
                      <td>
                        deg
                      </td>
                      <td>
                        rotation along the z axis
                      </td>
                    </tr>
                    <tr>
                      <td>
                        index
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        index of the sticker to rotate
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        1 in case of success, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementList()
                </h4>
                <p class="elementAPI_method_description">
                  list of all the uid of the elements of this construct
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        list
                      </td>
                      <td>
                        list of elements uids
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementName(uid)
                </h4>
                <p class="elementAPI_method_description">
                  name of the element, identified by its uid
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        uid
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the uid of the element
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        name of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementType(uid)
                </h4>
                <p class="elementAPI_method_description">
                  type of the element, identified by its uid
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        uid
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the uid of the element
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the type of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementHitPoints(uid)
                </h4>
                <p class="elementAPI_method_description">
                  current level of hitpoints of the element, identified by its uid
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        uid
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the uid of the element
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        current level of hitpoints of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementMaxHitPoints(uid)
                </h4>
                <p class="elementAPI_method_description">
                  max level of hitpoints of the element, identified by its uid
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        uid
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the uid of the element
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        max level of hitpoints of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getElementMass(uid)
                </h4>
                <p class="elementAPI_method_description">
                  mass of the element, identified by its uid
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        uid
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the uid of the element
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        kg
                      </td>
                      <td>
                        mass of the element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getAltitude()
                </h4>
                <p class="elementAPI_method_description">
                  altitude above sea level, with respect to the closest planet (zero in space)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m
                      </td>
                      <td>
                        the sea level altitude
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  g()
                </h4>
                <p class="elementAPI_method_description">
                  local gravity intensity
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s2
                      </td>
                      <td>
                        the gravitation acceleration where the construct is located
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldGravity()
                </h4>
                <p class="elementAPI_method_description">
                  local gravity vector in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s2
                      </td>
                      <td>
                        the local gravity field vector in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldVertical()
                </h4>
                <p class="elementAPI_method_description">
                  vertical unit vector along gravity, in world coordinates (zero in space)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s2
                      </td>
                      <td>
                        the local vertical vector in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getAngularVelocity()
                </h4>
                <p class="elementAPI_method_description">
                  the construct angular velocity, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        rad/s
                      </td>
                      <td>
                        angular velocity vector, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldAngularVelocity()
                </h4>
                <p class="elementAPI_method_description">
                  the construct angular velocity, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        rad/s
                      </td>
                      <td>
                        angular velocity vector, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getAngularAcceleration()
                </h4>
                <p class="elementAPI_method_description">
                  the construct angular acceleration, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        rad/s2
                      </td>
                      <td>
                        angular acceleration vector, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldAngularAcceleration()
                </h4>
                <p class="elementAPI_method_description">
                  the construct angular acceleration, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        rad/s2
                      </td>
                      <td>
                        angular acceleration vector, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getVelocity()
                </h4>
                <p class="elementAPI_method_description">
                  the construct linear velocity, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s
                      </td>
                      <td>
                        linear velocity vector, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldVelocity()
                </h4>
                <p class="elementAPI_method_description">
                  the construct linear velocity, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s
                      </td>
                      <td>
                        linear velocity vector, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getWorldAcceleration()
                </h4>
                <p class="elementAPI_method_description">
                  the construct linear acceleration, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s2
                      </td>
                      <td>
                        linear acceleration vector, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getAcceleration()
                </h4>
                <p class="elementAPI_method_description">
                  the construct linear acceleration, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        m/s2
                      </td>
                      <td>
                        linear acceleration vector, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructOrientationUp()
                </h4>
                <p class="elementAPI_method_description">
                  the construct current orientation up vector in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        up vector of current orientation, in local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructOrientationRight()
                </h4>
                <p class="elementAPI_method_description">
                  the construct current orientation right vector in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        right vector of current orientation, in local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructOrientationForward()
                </h4>
                <p class="elementAPI_method_description">
                  the construct current orientation forward vector in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        forward vector of current orientation, in local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldOrientationUp()
                </h4>
                <p class="elementAPI_method_description">
                  the construct current orientation up vector in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        up vector of current orientation, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldOrientationRight()
                </h4>
                <p class="elementAPI_method_description">
                  the construct current orientation right vector in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        right vector of current orientation, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldOrientationForward()
                </h4>
                <p class="elementAPI_method_description">
                  the construct current orientation forward vector in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        forward vector of current orientation, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Screen_Unit" title="Screen Unit">
                Screen Unit
              </h3>
              <p class="elementAPI_description">
                Screen Unit are capable to display any html code or text message, you can use them to create visually interactivefeedback for your running Lua script by connecting one or more of them to your Control Unit.
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  addText(x,y,fontSize,text)
                </h4>
                <p class="elementAPI_method_description">
                  displays the given text at the given coordinates in the screen, and return an id to move it later
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        horizontal position, as a percentage of the screen width
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        vertical position, as a percentage of the screen height
                      </td>
                    </tr>
                    <tr>
                      <td>
                        fontSize
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        text font size, as a percentage of the screen width
                      </td>
                    </tr>
                    <tr>
                      <td>
                        text
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the text to display
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        an integer id that can be used later to update/remove the added element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setCenteredText(text)
                </h4>
                <p class="elementAPI_method_description">
                  displays the given text centered in the screen with a font to maximize its visibility
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        text
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the text to display
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setHTML(html)
                </h4>
                <p class="elementAPI_method_description">
                  set the whole screen html content (overrides anything already set)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        html
                      </td>
                      <td>
                        html
                      </td>
                      <td>
                        the html content to display
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  addContent(x,y,html)
                </h4>
                <p class="elementAPI_method_description">
                  displays the given html content at the given coordinates in the screen, and return an id to move it later
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        horizontal position, as a percentage of the screen width
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        vertical position, as a percentage of the screen height
                      </td>
                    </tr>
                    <tr>
                      <td>
                        html
                      </td>
                      <td>
                        html
                      </td>
                      <td>
                        the html content to display, which can contain SVG elements to make drawings
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        an integer id that can be used later to update/remove the added element
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setSVG(svg)
                </h4>
                <p class="elementAPI_method_description">
                  displays SVG code (anything that fits within a &lt;svg&gt; section), which overrides any preexisting content
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        svg
                      </td>
                      <td>
                        svg
                      </td>
                      <td>
                        the SVG content to display, which fits inside a 1920x1080 canvas
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  resetContent(id,html)
                </h4>
                <p class="elementAPI_method_description">
                  update the element with the given id (returned by setContent) with a new html content
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        an integer id that is used to identify the element in the screen. Methods such as setContent return the id that you can store to use later here.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        html
                      </td>
                      <td>
                        html
                      </td>
                      <td>
                        the html content to display, which can contain SVG elements to make drawings
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deleteContent(id)
                </h4>
                <p class="elementAPI_method_description">
                  delete the element with the given id (returned by setContent)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        an integer id that is used to identify the element in the screen. Methods such as setContent return the id that you can store to use later here.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  showContent(id, state)
                </h4>
                <p class="elementAPI_method_description">
                  update the visibility of the element with the given id (returned by setContent)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        an integer id that is used to identify the element in the screen. Methods such as setContent return the id that you can store to use later here.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        state
                      </td>
                      <td>
                        0/1
                      </td>
                      <td>
                        0 = invisible, 1 = visible
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  moveContent(id,x,y)
                </h4>
                <p class="elementAPI_method_description">
                  move the element with the given id (returned by setContent) to a new position in the screen
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        horizontal position, as a percentage of the screen width
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        0..100
                      </td>
                      <td>
                        vertical position, as a percentage of the screen height
                      </td>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        an integer id that is used to identify the element in the screen. Methods such as setContent return the id that you can store to use later here.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMouseX()
                </h4>
                <p class="elementAPI_method_description">
                  returns the x coordinate of the position pointed at in the screen
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        the x position in percentage of screen width and -1 if nothing is pointed at
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMouseY()
                </h4>
                <p class="elementAPI_method_description">
                  returns the y coordinate of the position pointed at in the screen
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        the y position in percentage of screen height and -1 if nothing is pointed at
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMouseState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of the mouse click
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0 or 1
                      </td>
                      <td>
                        0 when the mouse is not clicked and 1 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  clear()
                </h4>
                <p class="elementAPI_method_description">
                  clear the screen
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  mouseDown(x,y)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when the player starts a click on the screen unit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        x coordinate of the click in percentage of the screen width
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        y coordinate of the click in percentage of the screen height
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  mouseUp(x,y)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when the player releases a click on the screen unit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        x
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        x coordinate of the click in percentage of the screen width
                      </td>
                    </tr>
                    <tr>
                      <td>
                        y
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        y coordinate of the click in percentage of the screen height
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Detection_Zone_Unit" title="Detection Zone Unit">
                Detection Zone Unit
              </h3>
              <p class="elementAPI_description">
                Detect the intrusion of any player inside the effect zone
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  enter(id)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  a player just entered the zone
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the player. Use database.getPlayer(id).name to retrieve its name
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  leave(id)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  a player just left the zone
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the player. Use database.getPlayer(id).name to retrieve its name
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Gyro_Unit" title="Gyro Unit">
                Gyro Unit
              </h3>
              <p class="elementAPI_description">
                A general kinematic unit to obtain information about the ship orientation, velocity and acceleration
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  selects this gyro as the main gyro used for ship orientation
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  deselects this gyro as the main gyro used for ship orientation, using the core unit instead
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of activation of the gyro
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the gyro
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the gyro is used for ship orientation, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  localUp()
                </h4>
                <p class="elementAPI_method_description">
                  the up vector of the Gyro Unit, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        normalized up vector of the gyro unit, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  localForward()
                </h4>
                <p class="elementAPI_method_description">
                  the forward vector of the Gyro Unit, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        normalized forward vector of the gyro unit, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  localRight()
                </h4>
                <p class="elementAPI_method_description">
                  the right vector of the Gyro Unit, in construct local coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        normalized right vector of the gyro unit, in construct local coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  worldUp()
                </h4>
                <p class="elementAPI_method_description">
                  the up vector of the Gyro Unit, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        normalized up vector of the gyro unit, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  worldForward()
                </h4>
                <p class="elementAPI_method_description">
                  the forward vector of the Gyro Unit, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        normalized forward vector of the gyro unit, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  worldRight()
                </h4>
                <p class="elementAPI_method_description">
                  the right vector of the Gyro Unit, in world coordinates
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        normalized right vector of the gyro unit, in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getPitch()
                </h4>
                <p class="elementAPI_method_description">
                  the pitch value relative to the gyro orientation and the local gravity
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        deg
                      </td>
                      <td>
                        the pitch angle in deg, relative to the gyro orientation and the local gravity
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getRoll()
                </h4>
                <p class="elementAPI_method_description">
                  the roll value relative to the gyro orientation and the local gravity
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        deg
                      </td>
                      <td>
                        the roll angle in deg, relative to the gyro orientation and the local gravity
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Laser_Detector_Unit" title="Laser Detector Unit">
                Laser Detector Unit
              </h3>
              <p class="elementAPI_description">
                Detect the hit of a laser
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the current state of the laser detector
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        0 if the detector has no laser pointed to it, 1 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  laserHit()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  a laser has just hit the detector
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  laserRelease()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  all lasers have left the detector
                </p>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Laser_Emitter_Unit" title="Laser Emitter Unit">
                Laser Emitter Unit
              </h3>
              <p class="elementAPI_description">
                Emits a laser ray that can be use to detect the passage of a player or on a Laser Detector Unit
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  start the laser
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  stop the laser
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the laser
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the laser
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the laser is activated, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Manual_Button" title="Manual Button">
                Manual Button
              </h3>
              <p class="elementAPI_description">
                Emits a signal when pressed, and as long as it is pressed
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the button
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the button is pressed, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  pressed()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  the button has been pressed
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  released()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  the button has been released
                </p>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Manual_Switch_Unit" title="Manual Switch Unit">
                Manual Switch Unit
              </h3>
              <p class="elementAPI_description">
                A manual switch that can be in an on/off state
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  activate()
                </h4>
                <p class="elementAPI_method_description">
                  activate the switch on
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  deactivate()
                </h4>
                <p class="elementAPI_method_description">
                  deactivate the switch off
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  toggle()
                </h4>
                <p class="elementAPI_method_description">
                  toggle the state of the switch
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the switch
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the switch is on, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  pressed()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  the button has been pressed
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  released()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  the button has been released
                </p>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Pressure_Tile_Unit" title="Pressure Tile Unit">
                Pressure Tile Unit
              </h3>
              <p class="elementAPI_description">
                Emits a signal when a player walks on the tile
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getState()
                </h4>
                <p class="elementAPI_method_description">
                  returns the state of activation of the pressure tile
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        -
                      </td>
                      <td>
                        1 when the tile is pressed, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  pressed()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  someone just stepped on the tile
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  released()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  someone left the tile
                </p>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Radar_Unit" title="Radar Unit">
                Radar Unit
              </h3>
              <p class="elementAPI_description">
                List local construct and access their id
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getRange()
                </h4>
                <p class="elementAPI_method_description">
                  returns the current range of the radar
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the range
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getEntries()
                </h4>
                <p class="elementAPI_method_description">
                  returns the list of construct ids currently detected in the range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        list
                      </td>
                      <td>
                        the list of construct ids, can be used with database.getConstruct to retrieve info about each construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructOwner(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the player id of the owner of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the player id of the owner. Use database.getPlayer(id) to retrieve info about it.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructSize(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the size of the bounding box of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the size of the construct in xyz coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructType(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the type of the given construct
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the type of the construct, can be 'static' or 'dynamic'
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldPos(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the world coordinates of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz world coordinates of the construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldVelocity(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the world coordinates of the speed of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz world coordinates of the velocity of the construct relative to absolute space
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructWorldAcceleration(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the world coordinates of the acceleration of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz world coordinates of the acceleration of the construct relative to absolute space
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructPos(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the radar-local coordinates of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz radar-local coordinates of the construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructVelocity(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the radar-local coordinates of the speed of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz radar-local coordinates of the velocity of the construct relative to absolute space
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructAcceleration(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the radar-local coordinates of the acceleration of the given construct, if in range
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the xyz radar-local coordinates of the acceleration of the construct relative to absolute space
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getConstructName(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the name of the given construct, if defined
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the construct
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the name of the construct
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  enter(id)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when a construct enters the range of the radar unit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        id of the construct, can be used with database.getConstruct to retrieve info about it
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  leave(id)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when a construct leaves the range of the radar unit
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        id of the construct, can be used with database.getConstruct to retrieve info about it
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Telemeter_Unit" title="Telemeter Unit">
                Telemeter Unit
              </h3>
              <p class="elementAPI_description">
                Measures the distance to obstacle in front of it
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getDistance()
                </h4>
                <p class="elementAPI_method_description">
                  returns the distance to the first obstacle in front of the telemeter
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the distance to the obstacle, up to getMaxDistance, or -1 if there is no obstacle (or the obstacle is further away than the max distance)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMaxDistance()
                </h4>
                <p class="elementAPI_method_description">
                  returns the max distance from which an obstacle can be detected (default is 20m)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        meter
                      </td>
                      <td>
                        the max distance to detectable obstacles
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_Library" title="Library">
                Library
              </h3>
              <p class="elementAPI_description">
                Contains a list of useful math and helper methods that would be slow to implement in Lua, and which aregiven here as fast C++ implementation
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  systemResolution3(vec_c1,vec_c2,vec_c3,vec_c0)
                </h4>
                <p class="elementAPI_method_description">
                  solve the 3D linear system M*x=c0 where M is defined by its column vectors c1,c2,c3
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        vec_c1
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the first column of the matrix M
                      </td>
                    </tr>
                    <tr>
                      <td>
                        vec_c2
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the second column of the matrix M
                      </td>
                    </tr>
                    <tr>
                      <td>
                        vec_c3
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the third column of the matrix M
                      </td>
                    </tr>
                    <tr>
                      <td>
                        vec_c0
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the target column vector of the system
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the vec3 solution of the above system
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  systemResolution2(vec_c1,vec_c2,vec_c0)
                </h4>
                <p class="elementAPI_method_description">
                  solve the 2D linear system M*x=c0 where M is defined by its column vectors c1,c2
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        vec_c1
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the first column of the matrix M
                      </td>
                    </tr>
                    <tr>
                      <td>
                        vec_c2
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the second column of the matrix M
                      </td>
                    </tr>
                    <tr>
                      <td>
                        vec_c0
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the target column vector of the system
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec2
                      </td>
                      <td>
                        the vec2 solution of the above system
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="elementAPI_element_block">
              <h3 class="elementAPI_name" id="bm_Lua_System" title="System">
                System
              </h3>
              <p class="elementAPI_description">
                System is a virtual Element that represents your computer. It gives access to events like key strokes or mouse movesthat can be used inside your scripts. It also gives you access to regular updates that can be used to pace the executionof your script.
              </p>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getActionKeyName(actionName)
                </h4>
                <p class="elementAPI_method_description">
                  return the currently key bound to the given action. Useful to display tips.
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the key associated to the given action name.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  showScreen(bool)
                </h4>
                <p class="elementAPI_method_description">
                  control the display of the control unit custom screen, where you can define customized display information in html.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        bool
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 show the screen, 0 hide the screen
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  setScreen(content)
                </h4>
                <p class="elementAPI_method_description">
                  set the content of the control unit custom screen with some html code.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        content
                      </td>
                      <td>
                        html
                      </td>
                      <td>
                        the html content you want to display on the screen widget. You can also use SVG here to make drawings.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  createWidgetPanel(label)
                </h4>
                <p class="elementAPI_method_description">
                  create an empty panel.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        label
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the title of the panel.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the panel id, or "" on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  destroyWidgetPanel(panelId)
                </h4>
                <p class="elementAPI_method_description">
                  destroy the panel.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        panelId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the panel id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 on success, 0 on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  createWidget(panelId, type)
                </h4>
                <p class="elementAPI_method_description">
                  create an empty widget and add it to a panel.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        panelId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the panel id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        type
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        widget type, determining how it will display data attached to id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the widget id, or "" on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  destroyWidget(widgetId)
                </h4>
                <p class="elementAPI_method_description">
                  destroy the widget.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        widgetId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the widget id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 on success, 0 on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  createData(dataJson)
                </h4>
                <p class="elementAPI_method_description">
                  create a data.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        dataJson
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data fields as json.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data id, or "" on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  destroyData(dataId)
                </h4>
                <p class="elementAPI_method_description">
                  destroy the data.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        dataId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 on success, 0 on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  updateData(dataId, dataJson)
                </h4>
                <p class="elementAPI_method_description">
                  update json associated to a data.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        dataId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        dataJson
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data fields as json.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 on success, 0 on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  addDataToWidget(dataId, widgetId)
                </h4>
                <p class="elementAPI_method_description">
                  add data to widget.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        dataId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        widgetId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the widget id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 on success, 0 on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  removeDataFromWidget(dataId, widgetId)
                </h4>
                <p class="elementAPI_method_description">
                  remove data from widget.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        dataId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the data id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        widgetId
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the widget id.
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 on success, 0 on failure.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMouseWheel()
                </h4>
                <p class="elementAPI_method_description">
                  return the current value of the mouse wheel
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        0..1
                      </td>
                      <td>
                        the current value of the mouse wheel
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMouseDeltaX()
                </h4>
                <p class="elementAPI_method_description">
                  return the current value of the mouse delta X
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        the current value of the mouse delta X
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMouseDeltaY()
                </h4>
                <p class="elementAPI_method_description">
                  return the current value of the mouse delta Y
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        the current value of the mouse delta Y
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMousePosX()
                </h4>
                <p class="elementAPI_method_description">
                  return the current value of the mouse pos X
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        the current value of the mouse pos X
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getMousePosY()
                </h4>
                <p class="elementAPI_method_description">
                  return the current value of the mouse pos Y
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        float
                      </td>
                      <td>
                        the current value of the mouse pos Y
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  lockView()
                </h4>
                <p class="elementAPI_method_description">
                  lock or unlock the mouse free look. Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        state
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 when locked and 0 to unlock
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  isViewLocked()
                </h4>
                <p class="elementAPI_method_description">
                  return the lock state of the mouse free look
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 when locked and 0 when unlocked
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  freeze(bool)
                </h4>
                <p class="elementAPI_method_description">
                  freeze the character, liberating the associated movement keys to be used by the script.  Note that this function is disabled if the player is not running the script explicitly (pressing F on the ControlUnit, vs via a plug signal)
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        bool
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 freeze the character, 0 unfreeze the character
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  isFrozen()
                </h4>
                <p class="elementAPI_method_description">
                  return the frozen status of the character (see 'freeze')
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        boolean
                      </td>
                      <td>
                        1 if the character is frozen, 0 otherwise
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getTime()
                </h4>
                <p class="elementAPI_method_description">
                  return the current time since the arrival of the Arkship
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        second
                      </td>
                      <td>
                        the current time in seconds, with a microsecond precision
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getPlayerName(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the name of the given player, if in range of visibility
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the player
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the name of the player
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  getPlayerWorldPos(id)
                </h4>
                <p class="elementAPI_method_description">
                  return the world position of the given player, if in range of visibility
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        id
                      </td>
                      <td>
                        int
                      </td>
                      <td>
                        the id of the player
                      </td>
                    </tr>
                    <tr>
                      <td>
                        return
                      </td>
                      <td>
                        vec3
                      </td>
                      <td>
                        the coordinates of the player in world coordinates
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_method">
                  print(msg)
                </h4>
                <p class="elementAPI_method_description">
                  print a message in the Lua console
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        msg
                      </td>
                      <td>
                        string
                      </td>
                      <td>
                        the message to print
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  actionStart(action)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when an action starts
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        action
                      </td>
                      <td>
                        Lua action
                      </td>
                      <td>
                        the action, represented as a string taken among the set of predefined Lua-available actions (you can check the drop down list to see what is available)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  actionStop(action)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted when an action stops
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        action
                      </td>
                      <td>
                        Lua action
                      </td>
                      <td>
                        the action, represented as a string taken among the set of predefined Lua-available actions (you can check the drop down list to see what is available)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  actionLoop(action)&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  emitted at each update as long as the action is maintained
                </p>
                <table class="elementAPI_table">
                  <tbody>
                    <tr>
                      <th>
                        Argument/Return
                      </th>
                      <th>
                        Type
                      </th>
                      <th>
                        Description
                      </th>
                    </tr>
                    <tr>
                      <td>
                        action
                      </td>
                      <td>
                        Lua action
                      </td>
                      <td>
                        the action, represented as a string taken among the set of predefined Lua-available actions (you can check the drop down list to see what is available)
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  update()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  game update event. This is equivalent to a timer set at 0 seconds, as update will go as fast as the FPS can go.
                </p>
              </div>
              <div class="elementAPI_method_block">
                <h4 class="elementAPI_event">
                  flush()&nbsp;&nbsp;
                  <i>
                    event
                  </i>
                </h4>
                <p class="elementAPI_method_description">
                  physics update. Do not use to put anything else by a call to updateICC on your control unit, as many functions are disabled when called from 'flush'. This is only to update the physics (engine control, etc), not to setup some gameplay code.
                </p>
              </div>
            </div>
          </section>
        </section>
        <section id="bm_section_dualsh" title="Dual.sh">
          <h1>
            Dual.sh
          </h1>
          <section id="bm_go_back" title="Go Back">
            <h2>
              Go Back
            </h2>
            <p>
              To return to the previous page,
              <a href="#" onclick="history.go(-1);">
                click here
              </a>
            </p>
          </section>
          <section id="bm_logout" title="Logout">
            <h2>
              Log Out
            </h2>
            <p>
              <a href="?action=logout">
                Click here to log out
              </a>
            </p>
          </section>
        </section>
      </div>
    </div>
    <script src="../js/web_codex_utils.js">
    </script>
    <script src="../js/web_codex_tools.js">
    </script>
    <script src="../js/web_codex.js">
    </script>
  </body>
</html>
<!--======== END INDIVIDUAL PAGES ========-->


EOL;
            }
        }
    }

    if ($found == FALSE) {
        echo '<h3>Unauthorized</h3>';
        echo '<p><a href="?action=logout">Log Out</a></p>';

    }
} else {
    echo '<h3>You must login before you can view this page, taking you back to the homepage now.</h3>';
    echo '<p>If this page does not automatically redirect you, <a href="http://dual.sh/index.php">click here.</a></p>';
    header('Refresh: 5; URL=http://dual.sh/index.php');
}

?>
