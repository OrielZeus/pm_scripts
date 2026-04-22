/**
 * Returns the current task information including task title, subtitle, and node.
 *
 * by Adriana Centellas
 */

let taskInfo = {
  OFF_TASK_TITLE: "",
  OFF_TASK_SUBTITLE: "",
  OFF_TASK_NODE: ""
};
let taskTitle = "";
let taskPrefix = "";
let titlesInfo = this.OFF_TITLES_INFO;

$(document).ready(function () {
  // Extract the text from the last breadcrumb item dynamically
  taskTitle = $('#breadcrumbs ol.breadcrumb li').last().text().trim();
  
  // Extract the first five characters as the task prefix
  taskPrefix = taskTitle.substring(0, 5);

  // Set task title and subtitle by comparing the task prefix with task codes
  taskInfo.OFF_TASK_TITLE = getTaskTitleByCode(taskPrefix, titlesInfo);
  taskInfo.OFF_TASK_SUBTITLE = getTaskSubtitleByCode(taskPrefix, titlesInfo);

  // Assign task prefix to task node
  taskInfo.OFF_TASK_NODE = taskPrefix;
});

/**
* Get task title by comparing task node with the task code.
*
* @param {string} code - The task node prefix to compare.
* @param {Array} titlesInfo - The array containing task codes and their associated titles.
* @returns {string} The task title or an empty string if not found.
*
* by Adriana Centellas
*/
function getTaskTitleByCode(code, titlesInfo) {
  const task = titlesInfo.find(item => item.OFF_TASK_CODE == code);
  return task ? task.OFF_TASK_TITLE : '';
}

/**
* Get task subtitle by comparing task node with the task code.
*
* @param {string} code - The task node prefix to compare.
* @param {Array} titlesInfo - The array containing task codes and their associated subtitles.
* @returns {string} The task subtitle or an empty string if not found.
*
* by Adriana Centellas
*/
function getTaskSubtitleByCode(code, titlesInfo) {
  const task = titlesInfo.find(item => item.OFF_TASK_CODE == code);
  return task ? task.OFF_TASK_SUBTITLE : '';
}

return taskInfo;