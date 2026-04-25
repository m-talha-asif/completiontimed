# Timed Page Activity (mod_completiontimed)

**Timed Page** is a specialized Moodle activity module designed to ensure students actually spend the required amount of time engaging with your content. Leveraging Moodle's custom completion rules, this plugin tracks active screen time and integrates securely with embedded video content to prevent skipping or idle completion. 

## ✨ Features

* **Custom Time Requirements**: Set a specific time (in seconds) that a student must actively view the page before it unlocks completion.
* **Rich Video Support**: Easily embed YouTube links or securely upload local video files (`.mp4`, `.webm`).
* **Auto-Calculate Duration**: Optionally allow the plugin to automatically set the required time to match the exact length of the embedded video.
* **Anti-Cheat Tracking**: The countdown timer is strictly tied to page visibility and video playback. If a student switches browser tabs, minimizes the window, or pauses the video, the timer automatically stops.
* **Locked Video Controls**: Standard video controls are disabled and replaced with custom Play/Pause buttons, preventing students from scrubbing or skipping ahead to trick the timer.
* **Attention Check (MCQ)**: Keep students engaged by configuring a mid-video Multiple Choice Question. 
    * Set a specific "Trigger Time" to pause the video and display the question overlay.
    * The video will only resume once the student submits an answer.
* **Results Reporting**: Teachers can access a dedicated "Results" tab to view exactly which answers students submitted for the attention checks, including grading status and timestamps.

## 📋 Requirements

* **Moodle Version:** 4.1 or higher (Requires `2022112800`).
* **Activity Completion:** Must be enabled at the site and course level to utilize the custom time completion rules.

## 🚀 Installation

1. Download the plugin and extract the files.
2. Rename the extracted folder to `completiontimed` (if it isn't already).
3. Place the `completiontimed` folder into the `mod/` directory of your Moodle installation.
    * The path should be: `[moodle_root]/mod/completiontimed`
4. Log in to your Moodle site as an Administrator.
5. Go to **Site administration > Notifications** to complete the plugin installation.

## ⚙️ Usage

1. Turn editing on within your Moodle course.
2. Click **Add an activity or resource** and select **Timed Page**.
3. Configure the activity settings:
    * **Name**: The title of the activity.
    * **Video Settings**: Choose between No Video, Local Video File, or YouTube URL.
    * **Video Attention Check (MCQ)**: Optional. Define the trigger time, question, options, and the correct answer.
    * **Settings**: Manually enter the "Required Time (Seconds)" or check "Use video length for required time".
4. Under **Activity completion**, set *Completion tracking* to **Show activity as complete when conditions are met**, and check the box for **View for required time**.
5. Save and display. 

## 📄 License
This plugin is developed for Moodle and inherits the GNU General Public License (GPL) standards utilized by the Moodle core platform.
