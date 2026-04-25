# YouTube Video Activity (mod_ytvideo)

**YouTube Video Activity** is a Moodle activity module designed to enhance multimedia learning by providing robust progress tracking for video content. Whether integrating external YouTube links or hosting local video files, this module ensures students never lose their place, automatically saving their watch history in the background.

## ✨ Features

* **Dual Media Support**: Instructors can provide a YouTube URL, upload a local video file using the rich text editor, or include both simultaneously. The form validates to ensure at least one source is provided.
* **Smart Resume Functionality**: When a student returns to the activity, the video automatically resumes from their last recorded timestamp.
* **Automated Background Tracking**: Playback progress is captured and transmitted securely via AJAX to the Moodle database.
    * *Active Tracking*: Progress is saved every 5 seconds while a video is playing.
    * *Exit Tracking*: Leveraging the browser's `visibilitychange` API, the module forces a final progress save if the student changes tabs, closes the browser, or pauses the video.
* **Independent Data Storage**: Uses a custom database table (`ytvideo_progress`) to maintain separate watch times for YouTube links and locally uploaded videos, preventing conflicts if both are used in the same activity.
* **Native API Integrations**: Utilizes the official YouTube Iframe API for embedded links and standard HTML5 event listeners for local media files.

## 📋 Requirements

* **Moodle Version:** 4.0 or higher (Requires `2022041900`).

## 🚀 Installation

1. Download the plugin and extract the files.
2. Rename the extracted folder to `ytvideo` (if it isn't already).
3. Place the `ytvideo` folder into the `mod/` directory of your Moodle installation.
    * The path should be: `[moodle_root]/mod/ytvideo`
4. Log in to your Moodle site as an Administrator.
5. Go to **Site administration > Notifications** to complete the plugin installation and initiate the database upgrades.

## ⚙️ Usage

Once installed, teachers and managers can add the module directly to their course topics.

1. Turn editing on within your Moodle course.
2. Click **Add an activity or resource** and select **YouTube Video**.
3. Provide a required **Name** for the activity.
4. Supply the video content:
    * Paste a standard YouTube link into the **YouTube URL** field.
    * AND/OR use the rich text editor under **Upload** to insert a local video file from your computer.
5. Save the activity. 

When a student accesses the module, JavaScript will automatically handle the loading, tracking, and resuming of their video progress.

## 📄 License
This plugin is developed for Moodle and inherits the GNU General Public License (GPL) standards utilized by the Moodle core platform.
