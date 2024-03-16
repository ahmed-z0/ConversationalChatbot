// audio-processor.js
class SilenceDetectionProcessor extends AudioWorkletProcessor {
    static get parameterDescriptors() {
      return [{ name: 'silenceThreshold', defaultValue: -45 }];
    }
  
    process(inputs, outputs, parameters) {
      const input = inputs[0];
      const output = outputs[0];
      const silenceThreshold = parameters.silenceThreshold[0];
      
      if (input && input.length > 0) {
        const inputData = input[0];
        let total = 0;
        
        for (let i = 0; i < inputData.length; ++i) {
          total += Math.abs(inputData[i]);
        }
        
        let average = total / inputData.length;
        let averageDb = 20 * Math.log10(average);
        this.port.postMessage({ averageDb });
      }
      
      return true;
    }
  }
  
  registerProcessor('silence-detection-processor', SilenceDetectionProcessor);



  