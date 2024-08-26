<?php

namespace Database\Seeders;

use App\Models\Dialplan;
use Illuminate\Database\Seeder;

class DialplanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $jsonData = '{
        //     "data": [
        //         {                   
        //             "country_code": "91",
        //             "destination": "user_exists",
        //             "context": "default",                    
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "10",
        //             "destination_status": "0",
        //             "description": "user exists",
        //             "account_id": "1",                    
        //             "dialplan_xml": "<extension name=\"user_exists\" continue=\"true\" uuid=\"ad50a615-17f8-48b9-bdd4-dff84cfcbad2\">\r\n\t<condition field=\"${loopback_leg}\" expression=\"^B$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"domain_name=${context}\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"\" expression=\"\">\r\n\t\t<action application=\"set\" data=\"user_exists=${user_exists id ${destination_number} ${domain_name}}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"from_user_exists=${user_exists id ${sip_from_user} ${sip_from_host}}\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\">\r\n\t\t<action application=\"set\" data=\"extension_uuid=${user_data ${destination_number}@${domain_name} var extension_uuid}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"extension_caller_id_name=${user_data ${destination_number}@${domain_name} var effective_caller_id_name}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"extension_caller_id_number=${user_data ${destination_number}@${domain_name} var effective_caller_id_number}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_all_enabled=${user_data ${destination_number}@${domain_name} var forward_all_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_all_destination=${user_data ${destination_number}@${domain_name} var forward_all_destination}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_busy_enabled=${user_data ${destination_number}@${domain_name} var forward_busy_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_busy_destination=${user_data ${destination_number}@${domain_name} var forward_busy_destination}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_no_answer_enabled=${user_data ${destination_number}@${domain_name} var forward_no_answer_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_no_answer_destination=${user_data ${destination_number}@${domain_name} var forward_no_answer_destination}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_user_not_registered_enabled=${user_data ${destination_number}@${domain_name} var forward_user_not_registered_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_user_not_registered_destination=${user_data ${destination_number}@${domain_name} var forward_user_not_registered_destination}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"forward_all_enabled=${user_data ${destination_number}@${domain_name} var forward_all_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"follow_me_enabled=${user_data ${destination_number}@${domain_name} var follow_me_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"do_not_disturb=${user_data ${destination_number}@${domain_name} var do_not_disturb}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"call_timeout=${user_data ${destination_number}@${domain_name} var call_timeout}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"missed_call_app=${user_data ${destination_number}@${domain_name} var missed_call_app}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"missed_call_data=${user_data ${destination_number}@${domain_name} var missed_call_data}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"toll_allow=${user_data ${destination_number}@${domain_name} var toll_allow}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"call_screen_enabled=${user_data ${destination_number}@${domain_name} var call_screen_enabled}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"user_record=${user_data ${destination_number}@${domain_name} var user_record}\" inline=\"true\"/>\r\n\t</condition>\r\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch"                    
        //         },
        //         {                   
        //             "country_code": "91",
        //             "destination": "caller_details",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "15",
        //             "destination_status": "0",
        //             "description": "caller details",
        //             "account_id": "1",
        //             "dialplan_xml": "<extension name=\"caller-details\" continue=\"true\" uuid=\"7bd3b407-4d30-4146-9227-e66125314f16\">\r\n\t<condition field=\"${caller_destination}\" expression=\"^$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"caller_destination=${destination_number}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"caller_id_name=${caller_id_name}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"caller_id_number=${caller_id_number}\"/>\r\n\t</condition>\r\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch"                    
        //         },
        //         {                   
        //             "country_code": "91",
        //             "destination": "call_direction",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "35",
        //             "destination_status": "0",
        //             "description": "call direction",
        //             "account_id": "1",                    
        //             "dialplan_xml": "<extension name=\"call-direction\" continue=\"true\" uuid=\"8cbccceb-0700-41cc-a620-32f25e78fec9\">\r\n\t<condition field=\"${call_direction}\" expression=\"^$\" break=\"never\">\r\n\t\t<action application=\"export\" data=\"call_direction=local\" inline=\"true\"/>\r\n\t</condition>\r\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": ""
        //         },
        //         {                   
        //             "country_code": "91",
        //             "destination": "is_local",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "35",
        //             "destination_status": "0",
        //             "description": "is local",
        //             "account_id": "1",                    
        //             "dialplan_xml": "<extension name=\"is_local\" continue=\"true\" uuid=\"5bd91c2d-38bd-4893-909a-654f72944268\">\r\n\t<condition field=\"${user_exists}\" expression=\"false\">\r\n\t\t<action application=\"lua\" data=\"app.lua is_local\"/>\r\n\t</condition>\r\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch"
        //         },
        //         {                  
        //             "country_code": "91",
        //             "destination": "local_extension",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "890",
        //             "destination_status": "0",
        //             "description": "local extensions",
        //             "account_id": "1",
        //             "dialplan_xml": "<extension name=\"local_extension\" continue=\"true\" uuid=\"535686c7-c745-4eb9-a92e-c355ce6f1195\">\r\n\t<condition field=\"${user_exists}\" expression=\"true\">\r\n\t\t<action application=\"export\" data=\"dialed_extension=${destination_number}\" inline=\"true\"/>\r\n\t\t<action application=\"limit\" data=\"hash ${domain_name} ${destination_number} ${limit_max} ${limit_destination}\" inline=\"false\"/>\r\n\t</condition>\r\n\t<condition field=\"\" expression=\"\">\r\n\t\t<action application=\"set\" data=\"hangup_after_bridge=true\"/>\r\n\t\t<action application=\"set\" data=\"continue_on_fail=true\"/>\r\n\t\t<action application=\"set\" data=\"initial_callee_id_name=${user_data(${dialed_extension}@${domain_name} var effective_caller_id_name)}\"/>\r\n\t\t<action application=\"hash\" data=\"insert/${domain_name}-call_return/${dialed_extension}/${caller_id_number}\"/>\r\n\t\t<action application=\"hash\" data=\"insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}\"/>\r\n\t\t<action application=\"set\" data=\"called_party_call_group=${user_data(${dialed_extension}@${domain_name} var call_group)}\"/>\r\n\t\t<action application=\"hash\" data=\"insert/${domain_name}-last_dial/${called_party_call_group}/${uuid}\"/>\r\n\t\t<action application=\"set\" data=\"api_hangup_hook=lua app.lua hangup\"/>\r\n\t\t<action application=\"export\" data=\"domain_name=${domain_name}\"/>\r\n\t\t<action application=\"bridge\" data=\"user/${destination_number}@${domain_name}\"/>\r\n\t\t<action application=\"lua\" data=\"app.lua failure_handler\"/>\r\n\t</condition>\r\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch"
        //         },
        //         {                   
        //             "country_code": "91",
        //             "destination": "user_record",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "50",
        //             "destination_status": "0",
        //             "description": "user record",
        //             "account_id": "1",
        //             "dialplan_xml": "<extension name=\"user_record\" continue=\"true\" uuid=\"ad80c96f-33a9-4a08-8fae-e9291fcaff0f\">\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^all$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^inbound$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^inbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^outbound$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^outbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^local$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^local$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"from_user_record=${user_data ${sip_from_user}@${sip_from_host} var user_record}\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^all$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^inbound$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^inbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^outbound$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^outbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^local$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^local$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${record_session}\" expression=\"^true$\">\r\n\t\t<action application=\"set\" data=\"record_path=${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"record_name=${uuid}.${record_ext}\" inline=\"true\"/>\r\n\t\t<action application=\"mkdir\" data=\"${record_path}\"/>\r\n\t\t<action application=\"set\" data=\"recording_follow_transfer=true\" inline=\"true\"/>\r\n\t\t<action application=\"bind_digit_action\" data=\"local,*5,api:uuid_record,${uuid} mask ${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/${uuid}.${record_ext},both,self\"/>\r\n\t\t<action application=\"bind_digit_action\" data=\"local,*6,api:uuid_record,${uuid} unmask ${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/${uuid}.${record_ext},both,self\"/>\r\n\t\t<action application=\"set\" data=\"record_append=true\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"record_in_progress=true\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"RECORD_ANSWER_REQ=true\"/>\r\n\t\t<action application=\"record_session\" data=\"${record_path}/${record_name}\"/>\r\n\t</condition>\r\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch"
        //         },
        //         {                    
        //             "country_code": "91",
        //             "destination": "callcenter",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "230",
        //             "destination_status": "0",
        //             "description": "call center queue",
        //             "account_id": "1",                   
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch",
        //             "dialplan_xml": "<extension name=\\"test queue\\" continue=\\"\\" uuid=\\"99b7f976-1511-4644-9914-7bf94dc43438\\">\\r\\n\\t<condition field=\\"destination_number\\" expression=\\"^([^#]+#)(.*)$\\" break=\\"never\\">\\r\\n\\t\\t<action application=\\"set\\" data=\\"caller_id_name=$2\\"/>\\r\\n\\t</condition>\\r\\n\\t<condition field=\\"destination_number\\" expression=\\"^(callcenter\\\\+)?1001$\\" >\\r\\n\\t\\t<action application=\\"answer\\" data=\\"\\"/>\\r\\n\\t\\t<action application=\\"set\\" data=\\"call_center_queue_uuid=06ebc76a-c416-4404-aa26-3e16577422b8\\"/>\\r\\n\\t\\t<action application=\\"set\\" data=\\"queue_extension=1001\\"/>\\r\\n\\t\\t<action application=\\"set\\" data=\\"cc_export_vars=${cc_export_vars},call_center_queue_uuid,sip_h_Alert-Info,hold_music\\"/>\\r\\n\\t\\t<action application=\\"set\\" data=\\"hangup_after_bridge=true\\"/>\\r\\n\\t\\t<action application=\\"set\\" data=\\"cc_base_score=5\\"/>\\r\\n\\t\\t<action application=\\"sleep\\" data=\\"1000\\"/>\\r\\n\\t\\t<action application=\\"say\\" data=\\"\\"/>\\r\\n\\t\\t<action application=\\"callcenter\\" data=\\"1001@192.168.1.150\\"/>\\r\\n\\t\\t<action application=\\"\\" data=\\"\\"/>\\r\\n\\t</condition>\\r\\n</extension>"
        //         },
        //         {                    
        //             "country_code": "91",
        //             "destination": "agent_status *22",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "200",
        //             "destination_status": "0",
        //             "description": "agent status",
        //             "account_id": "1",
        //             "dialplan_xml": "",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch",
        //             "dialplan_xml": "<extension name=\\"agent_status\\" continue=\\"false\\" uuid=\\"ea8c76d8-5071-4f8c-b050-07eafaf5c670\\">\\r\\n\\t<condition field=\\"destination_number\\" expression=\\"^\\\\*22$\\" break=\\"on-true\\">\\r\\n\\t\\t<action application=\\"set\\" data=\\"agent_id=${sip_from_user}\\"/>\\r\\n\\t\\t<action application=\\"lua\\" data=\\"app.lua agent_status\\"/>\\r\\n\\t</condition>\\r\\n\\t<condition field=\\"destination_number\\" expression=\\"^(?:agent\\\\+|\\\\*22)(.+)$\\">\\r\\n\\t\\t<action application=\\"set\\" data=\\"agent_id=$1\\"/>\\r\\n\\t\\t<action application=\\"lua\\" data=\\"app.lua agent_status\\"/>\\r\\n\\t</condition>\\r\\n</extension>"
        //         },
        //         {                   
        //             "country_code": "91",
        //             "destination": "agent_status_id *23",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.1.150",
        //             "order": "210",
        //             "destination_status": "0",
        //             "description": "agent status id ",
        //             "account_id": "1",
        //             "dialplan_enabled": "1",
        //             "hostname": "freeswitch",
        //             "dialplan_xml": "<extension name=\\"agent_status_id\\" continue=\\"false\\" uuid=\\"fd25b8f9-13e8-466e-b429-1d2211a39cbe\\">\\r\\n\\t<condition field=\\"destination_number\\" expression=\\"^\\\\*23$\\">\\r\\n\\t\\t<action application=\\"set\\" data=\\"agent_id=\\"/>\\r\\n\\t\\t<action application=\\"lua\\" data=\\"app.lua agent_status\\"/>\\r\\n\\t</condition>\\r\\n</extension>"
        //         },
        //         {
        //             "country_code": "91",
        //             "destination": "user_record",
        //             "context": "default",
        //             "usage": "voice",
        //             "domain": "192.168.2.225",
        //             "order": "50",
        //             "destination_status": "0",
        //             "description": "user record",
        //             "account_id": "1",
        //             "dialplan_xml": "<extension name=\"user_record\" continue=\"true\" uuid=\"ad80c96f-33a9-4a08-8fae-e9291fcaff0f\">\\r\\n    <condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${user_record}\" expression=\"^all$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${call_direction}\" expression=\"^inbound$\" break=\"never\"/>\\r\\n    <condition field=\"${user_record}\" expression=\"^inbound$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${call_direction}\" expression=\"^outbound$\" break=\"never\"/>\\r\\n    <condition field=\"${user_record}\" expression=\"^outbound$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${call_direction}\" expression=\"^local$\" break=\"never\"/>\\r\\n    <condition field=\"${user_record}\" expression=\"^local$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"from_user_record=${user_data ${sip_from_user}@${sip_from_host} var user_record}\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${from_user_record}\" expression=\"^all$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${call_direction}\" expression=\"^inbound$\" break=\"never\"/>\\r\\n    <condition field=\"${from_user_record}\" expression=\"^inbound$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${call_direction}\" expression=\"^outbound$\" break=\"never\"/>\\r\\n    <condition field=\"${from_user_record}\" expression=\"^outbound$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\\r\\n    <condition field=\"${call_direction}\" expression=\"^local$\" break=\"never\"/>\\r\\n    <condition field=\"${from_user_record}\" expression=\"^local$\" break=\"never\">\\r\\n        <action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\\r\\n    </condition>\\r\\n    <condition field=\"${record_session}\" expression=\"^true$\">\\r\\n        <action application=\"set\" data=\"record_path=/${domain_name}/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}\" inline=\"true\"/>\\r\\n        <action application=\"set\" data=\"record_name=${uuid}.${record_ext}\" inline=\"true\"/>\\r\\n        <action application=\"mkdir\" data=\"${record_path}\"/>\\r\\n        <action application=\"set\" data=\"recording_follow_transfer=true\" inline=\"true\"/>\\r\\n        <action application=\"bind_digit_action\" data=\"local,*5,api:uuid_record,${uuid} mask ${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/${uuid}.${record_ext},both,self\"/>\\r\\n        <action application=\"bind_digit_action\" data=\"local,*6,api:uuid_record,${uuid} unmask ${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/${uuid}.${record_ext},both,self\"/>\\r\\n        <action application=\"set\" data=\"record_append=true\" inline=\"true\"/>\\r\\n        <action application=\"set\" data=\"record_in_progress=true\" inline=\"true\"/>\\r\\n        <action application=\"set\" data=\"RECORD_ANSWER_REQ=true\"/>\\r\\n        <action application=\"set\" data=\"record_ext=wav\" inline=\"true\"/>\\r\\n        <action application=\"set\" data=\"record_path_complete=https://crmdata-test.s3.us-east-2.amazonaws.com${record_path}/${record_name}${record_ext}\" inline=\"true\"/>\\r\\n        <action application=\"record_session\" data=\"https://crmdata-test.s3.us-east-2.amazonaws.com${record_path}/${record_name}wav\"/>\\r\\n    </condition>\\r\\n</extension>",
        //             "dialplan_enabled": "1",
        //             "hostname": ""
        //         }             
        //     ]
        // }';

        $jsonData = '{
            "data": [
                {
                    "country_code": "91",
                    "destination": "user_exists",
                    "context": "default",                 
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "10",
                    "destination_status": "0",
                    "description": "user exists",
                    "account_id": "1",                  
                    "dialplan_xml": "<extension name=\"user_exists\" continue=\"true\" uuid=\"ad50a615-17f8-48b9-bdd4-dff84cfcbad2\">\n\t<condition field=\"${loopback_leg}\" expression=\"^B$\" break=\"never\">\n\t\t<action application=\"set\" data=\"domain_name=${context}\" inline=\"true\"/>\n\t</condition>\n\t<condition field=\"\" expression=\"\">\n\t\t<action application=\"set\" data=\"user_exists=${user_exists id ${destination_number} ${domain_name}}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"from_user_exists=${user_exists id ${sip_from_user} ${sip_from_host}}\" inline=\"true\"/>\n\t</condition>\n\t<condition field=\"${user_exists}\" expression=\"^true$\">\n\t\t<action application=\"set\" data=\"extension_uuid=${user_data ${destination_number}@${domain_name} var extension_uuid}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"extension_caller_id_name=${user_data ${destination_number}@${domain_name} var effective_caller_id_name}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"extension_caller_id_number=${user_data ${destination_number}@${domain_name} var effective_caller_id_number}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_all_enabled=${user_data ${destination_number}@${domain_name} var forward_all_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_all_destination=${user_data ${destination_number}@${domain_name} var forward_all_destination}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_busy_enabled=${user_data ${destination_number}@${domain_name} var forward_busy_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_busy_destination=${user_data ${destination_number}@${domain_name} var forward_busy_destination}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_no_answer_enabled=${user_data ${destination_number}@${domain_name} var forward_no_answer_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_no_answer_destination=${user_data ${destination_number}@${domain_name} var forward_no_answer_destination}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_user_not_registered_enabled=${user_data ${destination_number}@${domain_name} var forward_user_not_registered_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_user_not_registered_destination=${user_data ${destination_number}@${domain_name} var forward_user_not_registered_destination}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"forward_all_enabled=${user_data ${destination_number}@${domain_name} var forward_all_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"follow_me_enabled=${user_data ${destination_number}@${domain_name} var follow_me_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"do_not_disturb=${user_data ${destination_number}@${domain_name} var do_not_disturb}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"call_timeout=${user_data ${destination_number}@${domain_name} var call_timeout}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"missed_call_app=${user_data ${destination_number}@${domain_name} var missed_call_app}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"missed_call_data=${user_data ${destination_number}@${domain_name} var missed_call_data}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"toll_allow=${user_data ${destination_number}@${domain_name} var toll_allow}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"call_screen_enabled=${user_data ${destination_number}@${domain_name} var call_screen_enabled}\" inline=\"true\"/>\n\t\t<action application=\"set\" data=\"user_record=${user_data ${destination_number}@${domain_name} var user_record}\" inline=\"true\"/>\n\t</condition>\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                   
                },
                {
                    "country_code": "91",
                    "destination": "caller_details",
                    "context": "default",
                    "dial_action": "1000",            
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "15",
                    "destination_status": "0",
                    "description": "caller details",
                    "account_id": "1",                
                    "dialplan_xml": "<extension name=\"caller-details\" continue=\"true\" uuid=\"7bd3b407-4d30-4146-9227-e66125314f16\">\r\n\t<condition field=\"${caller_destination}\" expression=\"^$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"caller_destination=${destination_number}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"caller_id_name=${caller_id_name}\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"caller_id_number=${caller_id_number}\"/>\r\n\t</condition>\r\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                
                },
                {
                    "country_code": "91",
                    "destination": "call_direction",
                    "context": "default",                  
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "35",
                    "destination_status": "0",
                    "description": "call direction",
                    "account_id": "1",                    
                    "dialplan_xml": "<extension name=\"call-direction\" continue=\"true\" uuid=\"8cbccceb-0700-41cc-a620-32f25e78fec9\">\r\n\t<condition field=\"${call_direction}\" expression=\"^$\" break=\"never\">\r\n\t\t<action application=\"export\" data=\"call_direction=local\" inline=\"true\"/>\r\n\t</condition>\r\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                    
                },
                {
                    "country_code": "91",
                    "destination": "is_local",
                    "context": "default",                  
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "35",
                    "destination_status": "0",
                    "description": "is local",
                    "account_id": "1",                   
                    "dialplan_xml": "<extension name=\"is_local\" continue=\"true\" uuid=\"5bd91c2d-38bd-4893-909a-654f72944268\">\r\n\t<condition field=\"${user_exists}\" expression=\"false\">\r\n\t\t<action application=\"lua\" data=\"app.lua is_local\"/>\r\n\t</condition>\r\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                   
                },
                {
                    "country_code": "91",
                    "destination": "local_extension",
                    "context": "default",                 
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "890",
                    "destination_status": "0",
                    "description": "local extensions",
                    "account_id": "1",                    
                    "dialplan_xml": "<extension name=\"local_extension\" continue=\"true\" uuid=\"535686c7-c745-4eb9-a92e-c355ce6f1195\">\r\n\t<condition field=\"${user_exists}\" expression=\"true\">\r\n\t\t<action application=\"export\" data=\"dialed_extension=${destination_number}\" inline=\"true\"/>\r\n\t\t<action application=\"limit\" data=\"hash ${domain_name} ${destination_number} ${limit_max} ${limit_destination}\" inline=\"false\"/>\r\n\t</condition>\r\n\t<condition field=\"\" expression=\"\">\r\n\t\t<action application=\"set\" data=\"hangup_after_bridge=true\"/>\r\n\t\t<action application=\"set\" data=\"continue_on_fail=true\"/>\r\n\t\t<action application=\"set\" data=\"initial_callee_id_name=${user_data(${dialed_extension}@${domain_name} var effective_caller_id_name)}\"/>\r\n\t\t<action application=\"hash\" data=\"insert/${domain_name}-call_return/${dialed_extension}/${caller_id_number}\"/>\r\n\t\t<action application=\"hash\" data=\"insert/${domain_name}-last_dial_ext/${dialed_extension}/${uuid}\"/>\r\n\t\t<action application=\"set\" data=\"called_party_call_group=${user_data(${dialed_extension}@${domain_name} var call_group)}\"/>\r\n\t\t<action application=\"hash\" data=\"insert/${domain_name}-last_dial/${called_party_call_group}/${uuid}\"/>\r\n\t\t<action application=\"set\" data=\"api_hangup_hook=lua app.lua hangup\"/>\r\n\t\t<action application=\"export\" data=\"domain_name=${domain_name}\"/>\r\n\t\t<action application=\"bridge\" data=\"user/${destination_number}@${domain_name}\"/>\r\n\t\t<action application=\"lua\" data=\"app.lua failure_handler\"/>\r\n\t</condition>\r\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                    
                },
                {
                    "country_code": "91",
                    "destination": "user_record",
                    "context": "default",                   
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "50",
                    "destination_status": "0",
                    "description": "user record",
                    "account_id": "1",                  
                    "dialplan_xml": "<extension name=\"user_record\" continue=\"true\" uuid=\"ad80c96f-33a9-4a08-8fae-e9291fcaff0f\">\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^all$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^inbound$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^inbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^outbound$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^outbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^local$\" break=\"never\"/>\r\n\t<condition field=\"${user_record}\" expression=\"^local$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"from_user_record=${user_data ${sip_from_user}@${sip_from_host} var user_record}\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^all$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^inbound$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^inbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^outbound$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^outbound$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${from_user_exists}\" expression=\"^true$\" break=\"never\"/>\r\n\t<condition field=\"${call_direction}\" expression=\"^local$\" break=\"never\"/>\r\n\t<condition field=\"${from_user_record}\" expression=\"^local$\" break=\"never\">\r\n\t\t<action application=\"set\" data=\"record_session=true\" inline=\"true\"/>\r\n\t</condition>\r\n\t<condition field=\"${record_session}\" expression=\"^true$\">\r\n                <action application=\"set\" data=\"record_path=/${domain_name}/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}\" inline=\"true\"/>\r\n\t\t<!--<action application=\"set\" data=\"record_path=${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}\" inline=\"true\"/>-->\r\n\t\t<action application=\"set\" data=\"record_name=${uuid}.${record_ext}\" inline=\"true\"/>\r\n\t\t<action application=\"mkdir\" data=\"${record_path}\"/>\r\n\t\t<action application=\"set\" data=\"recording_follow_transfer=true\" inline=\"true\"/>\r\n\t\t<action application=\"bind_digit_action\" data=\"local,*5,api:uuid_record,${uuid} mask ${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/${uuid}.${record_ext},both,self\"/>\r\n\t\t<action application=\"bind_digit_action\" data=\"local,*6,api:uuid_record,${uuid} unmask ${recordings_dir}/${domain_name}/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/${uuid}.${record_ext},both,self\"/>\r\n\t\t<action application=\"set\" data=\"record_append=true\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"record_in_progress=true\" inline=\"true\"/>\r\n\t\t<action application=\"set\" data=\"RECORD_ANSWER_REQ=true\"/>\r\n\t\t<!--<action application=\"record_session\" data=\"${record_path}/${record_name}wav\"/>-->\r\n                 <action application=\"set\"  data=\"record_ext=wav\" inline=\"true\"/>\r\n                <action application=\"set\" data=\"record_path_complete=https://crmdata-test.s3.us-east-2.amazonaws.com${record_path}/${record_name}${record_ext}\" inline=\"true\"/>\r\n                <action application=\"record_session\" data=\"https://crmdata-test.s3.us-east-2.amazonaws.com${record_path}/${record_name}wav\"/>\r\n\t</condition>\r\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                   
                },
                {
                    "type": "Outbound",
                    "country_code": "",
                    "destination": "11-digit",
                    "context": "default",
                    "caller_Id_name": "17609997119",
                    "caller_Id_number": "17609997119",
                    "usage": "voice",
                    "domain": "192.168.2.225",
                    "order": "100",
                    "destination_status": "0",
                    "description": "11 digits number",
                    "account_id": "1",
                    "dialplan_xml": "<extension name=\"Gateway.11d\" continue=\"false\" uuid=\"aa0779e9-9621-4763-8adf-423988927bc0\">\\r\\n\\t<condition field=\"${user_exists}\" expression=\"false\"/>\\r\\n\\t<condition field=\"destination_number\" expression=\"^\\\\+?(\\\\d{11})$\">\\r\\n\\t\\t<action application=\"export\" data=\"call_direction=outbound\" inline=\"true\"/>\\r\\n\\t\\t<action application=\"unset\" data=\"call_timeout\"/>\\r\\n\\t\\t<action application=\"set\" data=\"hangup_after_bridge=true\"/>\\r\\n\\t\\t<action application=\"set\" data=\"effective_caller_id_name=${outbound_caller_id_name}\"/>\\r\\n\\t\\t<action application=\"set\" data=\"effective_caller_id_number=17609997119\"/>\\r\\n\\t\\t<action application=\"set\" data=\"inherit_codec=true\"/>\\r\\n\\t\\t<action application=\"set\" data=\"ignore_display_updates=true\"/>\\r\\n\\t\\t<action application=\"set\" data=\"callee_id_number=$1\"/>\\r\\n\\t\\t<action application=\"set\" data=\"continue_on_fail=1,2,3,6,18,21,27,28,31,34,38,41,42,44,58,88,111,403,501,602,607,809\"/>\\r\\n\\t\\t<action application=\"bridge\" data=\"sofia/gateway/1/$1\"/>\\r\\n\\t</condition>\\r\\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"
                },                
                {
                    "type": "Inbound",
                    "country_code": "",
                    "destination": "inbound-test",
                    "context": "public",               
                    "usage": "voice",
                    "domain": "192.168.2.252",
                    "order": "100",
                    "destination_status": "0",
                    "description": "inbound test",
                    "account_id": "1",                  
                    "dialplan_xml": "<extension name=\"17072256399\" continue=\"false\" uuid=\"f77f5507-c0ae-426b-8504-140bf7194d81\">\r\n\t<condition field=\"destination_number\" expression=\"^(18882610473)$\">\r\n\t\t<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\r\n\t\t<!--<action application=\"set\" data=\"domain_uuid=b0bfda31-8c93-41b4-b571-5cf6972a0155\" inline=\"true\"/>-->\r\n\t\t<action application=\"set\" data=\"domain_name=192.168.2.225\" inline=\"true\"/>\r\n                <action application=\"bridge\" data=\"user/1002@${domain_name}\"/>\r\n<!--<action applicatin=\"transfer\" data=\"1002 XML 192.168.2.225\"/>-->\r\n\t</condition>\r\n</extension>",
                    "dialplan_enabled": "1",
                    "hostname": "debian"                   
                }                         
            ]
        }';

        // Convert JSON data to PHP associative array
        $arrayData = json_decode($jsonData, true);

        // Check if decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            die('JSON decode error: ' . json_last_error_msg());
        }

        // Extract the 'data' array from the associative array
        $data = $arrayData['data'];

        foreach ($data as $row) {
            Dialplan::create($row);
        }
    }
}
